<?php

namespace App\Exports;

use Throwable;
use Vtiful\Kernel\Excel;
use App\Models\ExportTask;
use App\Models\SalesPerformance;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Contracts\Queue\ShouldQueue;
use League\Flysystem\Local\LocalFilesystemAdapter;

class SalesPerformanceExport implements ShouldQueue
{
    use Queueable;

    protected ExportTask $task;
    protected array $request;
    protected ?int $user_id;

    /**
     * 存储文件系统配置
     * @var string
     */
    protected string $disk = 'public';

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

    public function __construct(array $request, ExportTask $task, int $user_id)
    {
        $this->task    = $task;
        $this->request = $request;
        $this->user_id = $user_id;
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

            // 设置导出文件名
            $sheet = $excel->constMemory(basename($this->task->file_path), 'Sheet1', false);

            // 设置表头
            $headers = [
                '员工',
                '职位',
                '工作日期',
                '业务类型',
                '接诊类型',
                '顾客姓名',
                '项目/产品名称',
                '应收金额',
                '实收金额',
                '欠款金额',
                '余额支付',
                '服务占比',
                '计提金额',
                '备注'
            ];
            $sheet->header($headers);

            // 设置列宽
            $sheet->setColumn('A:A', 13);
            $sheet->setColumn('B:B', 13);
            $sheet->setColumn('C:C', 15);
            $sheet->setColumn('D:D', 15);
            $sheet->setColumn('E:E', 10);
            $sheet->setColumn('F:F', 20);
            $sheet->setColumn('G:G', 40);
            $sheet->setColumn('N:N', 60);

            // 写入数据
            $query      = $this->getQuery();
            $position   = config('setting.sales_performance.position');
            $table_name = config('setting.sales_performance.table_name');

            // 分批处理数据并直接写入
            $query->chunk($this->chunkSize, function ($records) use ($sheet, $position, $table_name) {
                $batchData = [];
                foreach ($records as $row) {
                    $batchData[] = [
                        $row->user_name,
                        $position[$row->position] ?? '',
                        Carbon::parse($row->created_at)->toDateString(),
                        $table_name[$row->table_name] ?? '',
                        get_reception_type_name($row->reception_type),
                        $row->customer_name,
                        $row->product_name . $row->goods_name,
                        $row->payable,
                        $row->income,
                        $row->arrearage,
                        $row->deposit,
                        $row->rate,
                        $row->amount,
                        $row->remark,
                    ];
                }
                // 每一批数据直接写入文件
                if (!empty($batchData)) {
                    $sheet->data($batchData);
                }
            });

            // 导出文件
            $sheet->output();

            // 关闭 xlswriter
            $excel->close();

            // 更新任务状态为完成
            $this->task->update([
                'status'       => 'completed',
                'completed_at' => now(),
            ]);

        } catch (Throwable $exception) {
            $this->task->update([
                'status'        => 'failed',
                'failed_at'     => now(),
                'error_message' => $exception->getMessage(),
            ]);
        }
    }

    protected function getQuery()
    {
        $filters    = $this->request['filters'] ?? [];
        $keyword    = $this->request['keyword'] ?? null;
        $created_at = $this->request['created_at'];

        return SalesPerformance::query()
            ->select([
                'users.name as user_name',
                'sales_performance.position',
                'sales_performance.created_at',
                'sales_performance.table_name',
                'sales_performance.reception_type',
                'customer.name as customer_name',
                'sales_performance.product_name',
                'sales_performance.goods_name',
                'sales_performance.payable',
                'sales_performance.income',
                'sales_performance.arrearage',
                'sales_performance.deposit',
                'sales_performance.rate',
                'sales_performance.amount',
                'sales_performance.remark',
            ])
            ->leftJoin('users', 'users.id', '=', 'sales_performance.user_id')
            ->leftJoin('customer', 'customer.id', '=', 'sales_performance.customer_id')
            ->when($keyword, fn(Builder $query) => $query->where('customer.keyword', 'like', "%{$keyword}%"))
            ->whereBetween('sales_performance.created_at', [
                Carbon::parse($created_at[0])->startOfDay(),
                Carbon::parse($created_at[1])->endOfDay()
            ])
            ->queryConditions('ReportPerformanceSales', $filters)
            // 根据权限过滤
            ->when(!user($this->user_id)->hasAnyAccess(['superuser', 'sales_performance.view.all']), function (Builder $query) {
                $query->whereIn('sales_performance.user_id', user($this->user_id)->getUserIdsForSalesPerformance());
            })
            ->orderBy('sales_performance.created_at', 'desc');
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
     * 判断当前存储是否为本地存储
     * @return bool
     */
    protected function isLocalStorage(): bool
    {
        return Storage::disk($this->disk)->getAdapter() instanceof LocalFilesystemAdapter;
    }
}
