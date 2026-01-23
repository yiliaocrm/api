<?php

namespace App\Exports;

use Throwable;
use Vtiful\Kernel\Excel;
use App\Models\ExportTask;
use Illuminate\Bus\Queueable;
use App\Models\CashierRefundDetail;
use App\Events\Web\ExportCompleted;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Contracts\Queue\ShouldQueue;
use League\Flysystem\Local\LocalFilesystemAdapter;

class CashierRefundExport implements ShouldQueue
{
    use Queueable;

    protected ExportTask $task;
    protected array $request;
    protected int $user_id;
    protected string $tenant_id;

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

            // 设置导出文件名
            $sheet = $excel->constMemory(basename($this->task->file_path), 'Sheet1', false);

            // 设置表头
            $headers = [
                '退款单号',
                '收银单号',
                '顾客姓名',
                '顾客卡号',
                '项目名称',
                '物品名称',
                '套餐名称',
                '退款次数/数量',
                '退款金额',
                '退款备注',
                '结算科室',
                '收银人员',
                '收银时间',
            ];
            $sheet->header($headers);

            // 设置列宽
            $sheet->setColumn('A:A', 30);
            $sheet->setColumn('B:B', 30);
            $sheet->setColumn('C:C', 15);
            $sheet->setColumn('D:D', 20);
            $sheet->setColumn('E:E', 25);
            $sheet->setColumn('F:F', 25);
            $sheet->setColumn('G:G', 20);
            $sheet->setColumn('H:H', 15);

            // 查询数据
            $query = $this->getQuery();


            $setting = config('setting.customer_goods.status');

            // 分批处理数据并直接写入
            $query->chunk($this->chunkSize, function ($records) use ($sheet, $setting) {
                $batchData = [];
                foreach ($records as $row) {
                    $batchData[] = [
                        $row->cashier_refund_id,
                        $row->cashier_id,
                        $row->customer_name,
                        $row->customer_idcard,
                        $row->product_name,
                        $row->goods_name,
                        $row->package_name,
                        $row->times,
                        $row->amount,
                        $row->remark,
                        $row->department_id ? $row->department->name : '',
                        $row->user_id ? $row->user->name : '',
                        $row->created_at->toDateTimeString()
                    ];
                }
                // 每一批数据直接写入文件
                if (!empty($batchData)) {
                    $sheet->data($batchData);
                }
            });

            // 导出文件
            $sheet->output();

            // 关闭文件
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
        $sort    = $this->request['sort'] ?? 'created_at';
        $order   = $this->request['order'] ?? 'desc';
        $keyword = $this->request['keyword'] ?? null;
        $filters = $this->request['filters'] ?? [];

        return CashierRefundDetail::query()
            ->with([
                'user:id,name',
                'department:id,name'
            ])
            ->select([
                'cashier_refund_detail.*',
                'customer.name as customer_name',
                'customer.sex',
                'customer.age',
                'customer.idcard as customer_idcard',
            ])
            ->leftJoin('customer', 'customer.id', '=', 'cashier_refund_detail.customer_id')
            ->queryConditions('ReportCustomerRefund', $filters)
            ->when($keyword, fn(Builder $query) => $query->where('customer.keyword', 'like', '%' . $keyword . '%'))
            ->orderBy("cashier_refund_detail.{$sort}", $order);
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

    /**
     * 上传文件到云端存储并删除本地文件
     *
     * 当使用云存储（如 OSS、S3）时，xlswriter 生成的本地文件需要上传到云端，
     * 然后删除本地临时文件以节省服务器存储空间。
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
