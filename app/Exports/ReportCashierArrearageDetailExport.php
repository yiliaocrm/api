<?php

namespace App\Exports;

use Throwable;
use Carbon\Carbon;
use Vtiful\Kernel\Excel;
use App\Models\ExportTask;
use App\Models\Department;
use Illuminate\Bus\Queueable;
use App\Events\Web\ExportCompleted;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Contracts\Queue\ShouldQueue;
use League\Flysystem\Local\LocalFilesystemAdapter;

class ReportCashierArrearageDetailExport implements ShouldQueue
{
    use Queueable;

    protected ExportTask $task;
    protected array $request;
    protected ?int $user_id;
    protected string $tenant_id;

    /**
     * 分批处理数据的大小
     * @var int
     */
    protected int $chunkSize = 1000;

    /**
     * 设置任务超时时间
     * @var int
     */
    public int $timeout = 1200;

    /**
     * 存储文件系统配置
     * @var string
     */
    protected string $disk = 'public';

    public function __construct(array $request, ExportTask $task, string $tenant_id, int $user_id)
    {
        $this->task      = $task;
        $this->request   = $request;
        $this->user_id   = $user_id;
        $this->tenant_id = $tenant_id;
    }

    public function handle(): void
    {
        try {
            // 更新任务状态为处理中
            $this->task->update([
                'status'     => 'processing',
                'started_at' => now(),
            ]);

            // 获取存储路径
            $path = Storage::disk($this->disk)->path(dirname($this->task->file_path));

            // 检查并创建目录（仅本地存储需要）
            if ($this->isLocalStorage()) {
                if (!is_dir($path)) {
                    mkdir($path, 0755, true);
                }
            }

            // 初始化 xlswriter
            $excel = new Excel(['path' => $path]);
            $sheet = $excel->constMemory(basename($this->task->file_path), 'Sheet1', false);

            // 设置表头
            $headers = [
                '业务日期',
                '单据类型',
                '顾客姓名',
                '顾客卡号',
                '产品名称',
                '套餐名称',
                '物品名称',
                '数量',
                '规格',
                '金额',
                '备注',
                '销售人员',
                '结算科室',
                '结单人员'
            ];
            $sheet->header($headers);

            // 获取查询数据
            $data = $this->getQuery()->get();

            // 获取科室名称和用户名称
            $departmentIds = $data->pluck('department_id')->unique()->filter();
            $userIds       = $data->pluck('user_id')->unique()->filter();

            $departments = [];
            $users       = [];

            if ($departmentIds->isNotEmpty()) {
                $departments = Department::whereIn('id', $departmentIds)
                    ->pluck('name', 'id')
                    ->toArray();
            }

            if ($userIds->isNotEmpty()) {
                $users = DB::table('users')
                    ->whereIn('id', $userIds)
                    ->pluck('name', 'id')
                    ->toArray();
            }

            // 解析销售人员 JSON
            $data->transform(function ($item) use ($departments, $users) {
                $item->department_name = $departments[$item->department_id] ?? '';
                $item->user_name       = $users[$item->user_id] ?? '';
                // 解析销售人员 JSON
                $salesmanArray = json_decode($item->salesman, true);
                if (is_array($salesmanArray)) {
                    $item->salesman_names = implode(',', array_column($salesmanArray, 'name'));
                } else {
                    $item->salesman_names = '';
                }
                return $item;
            });

            // 分批处理数据并直接写入
            $chunkedData = $data->chunk($this->chunkSize);
            foreach ($chunkedData as $chunk) {
                $batchData = [];
                foreach ($chunk as $row) {
                    $batchData[] = [
                        $row->created_at ? Carbon::parse($row->created_at)->format('Y-m-d H:i:s') : '',
                        $row->type_name ?? '',
                        $row->customer_name ?? '',
                        $row->idcard ?? '',
                        $row->product_name ?? '',
                        $row->package_name ?? '',
                        $row->goods_name ?? '',
                        $row->times ?? '',
                        $row->specs ?? '',
                        $row->income ?? '',
                        $row->remark ?? '',
                        $row->salesman_names,
                        $row->department_name,
                        $row->user_name
                    ];
                }
                if (!empty($batchData)) {
                    $sheet->data($batchData);
                }
            }

            // 导出文件
            $sheet->output();
            $excel->close();

            // 如果使用云存储，上传文件到云端并删除本地文件
            if (!$this->isLocalStorage()) {
                $this->uploadToCloudAndDeleteLocalFile();
            }

            // 更新任务状态为完成
            $this->task->update([
                'status'       => 'completed',
                'completed_at' => now(),
            ]);

            // 触发导出完成事件
            event(new ExportCompleted($this->task, $this->tenant_id, $this->user_id));

        } catch (\Throwable $exception) {
            $this->task->update([
                'status'        => 'failed',
                'failed_at'     => now(),
                'error_message' => $exception->getMessage(),
            ]);
        }
    }

    protected function getQuery(): QueryBuilder
    {
        $date    = $this->request['date'];
        $keyword = $this->request['keyword'] ?? null;
        $type    = $this->request['type'] ?? null;

        $startDate = Carbon::parse($date[0])->startOfDay();
        $endDate   = Carbon::parse($date[1])->endOfDay();

        // 欠款单查询
        $arrearageQuery = DB::table('cashier_arrearage as ca')
            ->select([
                'ca.id',
                'ca.created_at',
                DB::raw("'arrearage' as type"),
                DB::raw("'欠款单' as type_name"),
                'ca.customer_id',
                'customer.name as customer_name',
                'customer.idcard',
                'ca.package_name',
                'ca.product_name',
                'ca.goods_name',
                'ca.times',
                'ca.specs',
                'ca.arrearage as income',
                DB::raw('NULL as remark'),
                'ca.salesman',
                'ca.department_id',
                'ca.user_id',
            ])
            ->leftJoin('customer', 'customer.id', '=', 'ca.customer_id')
            ->whereBetween('ca.created_at', [$startDate, $endDate])
            ->when($keyword, fn($query) => $query->where('customer.keyword', 'like', "%{$keyword}%"))
            ->when($type === 'arrearage' || $type === null || $type === '', fn($q) => $q)
            ->when($type === 'repayment', fn($q) => $q->whereRaw('1=0')); // 过滤掉欠款单

        // 还款单查询
        $repaymentQuery = DB::table('cashier_arrearage_detail as cad')
            ->select([
                'cad.id',
                'cad.created_at',
                DB::raw("'repayment' as type"),
                DB::raw("'还款单' as type_name"),
                'cad.customer_id',
                'customer.name as customer_name',
                'customer.idcard',
                'cad.package_name',
                'cad.product_name',
                'cad.goods_name',
                'cad.times',
                'cad.specs',
                'cad.income',
                'cad.remark',
                'cad.salesman',
                'cad.department_id',
                'cad.user_id',
            ])
            ->leftJoin('customer', 'customer.id', '=', 'cad.customer_id')
            ->whereBetween('cad.created_at', [$startDate, $endDate])
            ->when($keyword, fn(QueryBuilder $query) => $query->where('customer.keyword', 'like', "%{$keyword}%"))
            ->when($type === 'repayment' || $type === null || $type === '', fn($q) => $q)
            ->when($type === 'arrearage', fn($q) => $q->whereRaw('1=0')); // 过滤掉还款单

        // 合并查询
        return DB::query()->fromSub($arrearageQuery, 'arrearage')->unionAll($repaymentQuery);
    }

    /**
     * 任务失败时调用
     * @param Throwable $exception
     * @return void
     */
    public function failed(Throwable $exception): void
    {
        $this->task->update([
            'status'        => 'failed',
            'failed_at'     => now(),
            'error_message' => '导出任务执行失败: ' . $exception->getMessage(),
        ]);
    }

    /**
     * 判断是否使用本地存储
     */
    protected function isLocalStorage(): bool
    {
        return Storage::disk($this->disk)->getAdapter() instanceof LocalFilesystemAdapter;
    }

    /**
     * 上传文件到云端存储并删除本地文件
     */
    protected function uploadToCloudAndDeleteLocalFile(): void
    {
        // 从本地 public 盘获取文件流
        $stream = Storage::disk('public')->readStream($this->task->file_path);

        // 将文件流式上传到默认的云存储
        Storage::put($this->task->file_path, $stream);

        // 关闭文件流
        if (is_resource($stream)) {
            fclose($stream);
        }

        // 删除本地文件
        Storage::disk('public')->delete($this->task->file_path);
    }
}
