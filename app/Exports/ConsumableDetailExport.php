<?php

namespace App\Exports;

use Throwable;
use Vtiful\Kernel\Excel;
use App\Models\ExportTask;
use Illuminate\Bus\Queueable;
use App\Models\ConsumableDetail;
use App\Events\Web\ExportCompleted;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Contracts\Queue\ShouldQueue;
use League\Flysystem\Local\LocalFilesystemAdapter;

class ConsumableDetailExport implements ShouldQueue
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
                '单据编号',
                '单据日期',
                '消费项目',
                '商品名称',
                '出库仓库',
                '领料科室',
                '规格',
                '批号',
                '数量',
                '单位',
                '单价',
                '总价',
                '生产厂家',
                '生产日期',
                '过期时间',
                'SN码',
                '备注信息'
            ];
            $sheet->header($headers);

            // 设置列宽
            $sheet->setColumn('A:A', 12);
            $sheet->setColumn('B:B', 13);
            $sheet->setColumn('C:C', 18);
            $sheet->setColumn('D:D', 18);
            $sheet->setColumn('E:E', 18);
            $sheet->setColumn('F:F', 18);
            $sheet->setColumn('M:M', 20);

            // 查询数据
            $query = $this->getQuery();

            // 分批处理数据并直接写入
            $query->chunk($this->chunkSize, function ($records) use ($sheet) {
                $batchData = [];
                foreach ($records as $row) {
                    $batchData[] = [
                        $row->key,
                        $row->date,
                        $row->consumable->product_name,
                        $row->goods_name,
                        $row->warehouse->name,
                        $row->department->name,
                        $row->specs,
                        $row->batch_code,
                        $row->number,
                        $row->unit_name,
                        $row->price,
                        $row->amount,
                        $row->manufacturer_name,
                        $row->production_date,
                        $row->expiry_date,
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

            // 如果使用云存储，上传文件到云端并删除本地文件
            if (!$this->isLocalStorage()) {
                $this->uploadToCloudAndDeleteLocalFile();
            }

            // 更新任务状态为完成
            $this->task->update([
                'status'       => 'completed',
                'completed_at' => now(),
            ]);

            // 触发导出完成事件，通知前端
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

        return ConsumableDetail::query()
            ->with([
                'consumable',
                'warehouse:id,name',
                'department:id,name',
            ])
            ->select([
                'consumable_detail.*',
            ])
            ->leftJoin('goods', 'goods.id', '=', 'consumable_detail.goods_id')
            ->leftJoin('consumable', 'consumable.id', '=', 'consumable_detail.consumable_id')
            ->queryConditions('ReportConsumableDetail', $filters)
            ->when($keyword, fn(Builder $query) => $query->where('goods.keyword', 'like', '%' . $keyword . '%'))
            ->orderBy("consumable_detail.{$sort}", $order);
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
