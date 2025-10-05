<?php

namespace App\Exports;

use Throwable;
use Vtiful\Kernel\Excel;
use App\Models\ExportTask;
use Illuminate\Bus\Queueable;
use App\Models\PurchaseDetail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Contracts\Queue\ShouldQueue;
use League\Flysystem\Local\LocalFilesystemAdapter;

class PurchaseDetailExport implements ShouldQueue
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
                '单据日期',
                '单据编号',
                '供应厂商',
                '生产厂商',
                '仓库',
                '商品名称',
                '规格型号',
                '数量',
                '单位',
                '单价',
                '总价',
                '生产日期',
                '过期时间',
                '批号',
                'SN码',
                '备注'
            ];
            $sheet->header($headers);

            // 设置列宽
            $sheet->setColumn('A:A', 12);
            $sheet->setColumn('B:B', 20);
            $sheet->setColumn('C:C', 20);
            $sheet->setColumn('E:E', 15);
            $sheet->setColumn('F:F', 35);
            $sheet->setColumn('I:I', 10);
            $sheet->setColumn('J:J', 12);
            $sheet->setColumn('K:K', 12);
            $sheet->setColumn('M:M', 15);

            // 查询数据
            $query = $this->getQuery();

            // 提前判断用户权限，避免在循环中重复调用
            $hasPriceAccess = user($this->user_id)->hasAnyAccess(['superuser', 'view.purchase.price']);

            // 分批处理数据并直接写入
            $query->chunk($this->chunkSize, function ($records) use ($sheet, $hasPriceAccess) {
                $batchData = [];
                foreach ($records as $row) {
                    $batchData[] = [
                        $row->date,
                        $row->key,
                        $row->supplier->name ?? '',
                        $row->manufacturer_name,
                        $row->warehouse->name ?? '',
                        $row->goods_name,
                        $row->specs,
                        $row->number,
                        $row->unit_name,
                        $hasPriceAccess ? $row->price : '***',
                        $hasPriceAccess ? $row->amount : '***',
                        $row->production_date,
                        $row->expiry_date,
                        $row->batch_code,
                        $row->sncode,
                        $row->remark
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

    protected function getQuery(): Builder
    {
        $sort    = $this->request['sort'] ?? 'created_at';
        $order   = $this->request['order'] ?? 'desc';
        $keyword = $this->request['keyword'] ?? null;
        $filters = $this->request['filters'] ?? [];

        return PurchaseDetail::query()
            ->with([
                'supplier:id,name',
                'warehouse:id,name',
            ])
            ->select('purchase_detail.*')
            ->join('goods', 'goods.id', '=', 'purchase_detail.goods_id')
            ->when($keyword, fn(Builder $query) => $query->where('goods.keyword', 'like', '%' . $keyword . '%'))
            ->where('status', 2)
            ->queryConditions('ReportPurchaseDetail', $filters)
            ->orderBy("purchase_detail.{$sort}", $order);
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
