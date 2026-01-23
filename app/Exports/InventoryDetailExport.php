<?php

namespace App\Exports;

use Throwable;
use Vtiful\Kernel\Excel;
use App\Models\ExportTask;
use App\Models\InventoryDetail;
use App\Events\Web\ExportCompleted;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Contracts\Queue\ShouldQueue;
use League\Flysystem\Local\LocalFilesystemAdapter;

class InventoryDetailExport implements ShouldQueue
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

    public function __construct(array $request, ExportTask $task, string $tenant_id, int $user_id)
    {
        $this->task      = $task;
        $this->request   = $request;
        $this->tenant_id = $tenant_id;
        $this->user_id   = $user_id;
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
            $path = Storage::disk('public')->path(dirname($this->task->file_path));

            // 检查并创建目录
            if (!is_dir($path)) {
                mkdir($path, 0755, true);
            }

            // 初始化 xlswriter
            $excel = new Excel(['path' => $path]);

            // 设置导出文件名
            $sheet = $excel->constMemory(basename($this->task->file_path), 'Sheet1', false);

            // 设置表头
            $headers = [
                '单据日期',
                '单据编号',
                '商品ID',
                '商品名称',
                '规格型号',
                '仓库名称',
                '单位',
                '生产厂家',
                '生产日期',
                '过期时间',
                '批号',
                'SN码',
                '备注',
                '业务类型',
                '单价',
                '数量',
                '总价',
                '批次数量',
                '批次成本',
                '结存数量',
                '结存成本'
            ];
            $sheet->header($headers);

            // 设置列宽
            $sheet->setColumn('A:A', 12);
            $sheet->setColumn('B:B', 20);
            $sheet->setColumn('D:D', 35);
            $sheet->setColumn('E:E', 15);
            $sheet->setColumn('I:I', 12);
            $sheet->setColumn('J:J', 12);
            $sheet->setColumn('K:K', 12);

            // 查询数据
            $query = $this->getQuery();

            // 获取业务类型配置
            $detailableTypes = config('setting.inventory_detail.detailable_type') ?? [];

            // 分批处理数据并直接写入
            $query->chunk($this->chunkSize, function ($records) use ($sheet, $detailableTypes) {
                $batchData = [];
                foreach ($records as $row) {
                    $batchData[] = [
                        $row->date,
                        $row->key,
                        $row->goods_id,
                        $row->goods_name,
                        $row->specs,
                        $row->warehouse->name ?? $row->warehouse_id,
                        $row->unit_name,
                        $row->manufacturer_name,
                        $row->production_date,
                        $row->expiry_date,
                        $row->batch_code,
                        $row->sncode,
                        $row->remark,
                        $detailableTypes[$row->detailable_type] ?? '',
                        $row->price,
                        $row->number,
                        $row->amount,
                        $row->batchs_number,
                        $row->batchs_amount,
                        $row->inventory_number,
                        $row->inventory_amount
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

            // 上传到云端存储
            $this->uploadToCloudAndDeleteLocalFile();

            // 更新任务状态为完成
            $this->task->update([
                'status'       => 'completed',
                'completed_at' => now(),
            ]);

            // 触发导出完成事件，通知前端
            ExportCompleted::dispatch($this->task, $this->tenant_id, $this->user_id);

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
        $date       = $this->request['date'] ?? null;
        $goods_name = $this->request['goods_name'] ?? null;
        $filters    = $this->request['filters'] ?? [];
        $sort       = $this->request['sort'] ?? 'id';
        $order      = $this->request['order'] ?? 'desc';

        return InventoryDetail::query()
            ->with([
                'warehouse:id,name'
            ])
            ->select([
                'inventory_detail.*'
            ])
            ->leftJoin('warehouse', 'warehouse.id', '=', 'inventory_detail.warehouse_id')
            ->leftJoin('manufacturer', 'manufacturer.id', '=', 'inventory_detail.manufacturer_id')
            ->when($date, function (Builder $query) use ($date) {
                $query->whereBetween('inventory_detail.date', [$date[0], $date[1]]);
            })
            ->when($goods_name, fn(Builder $query) => $query->where('inventory_detail.goods_name', 'like', "%{$goods_name}%"))
            ->queryConditions('ReportInventoryDetail', $filters)
            ->orderBy("inventory_detail.{$sort}", $order);
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
     * 上传文件到云端存储并删除本地文件
     */
    protected function uploadToCloudAndDeleteLocalFile(): void
    {
        // 如果使用的是本地存储，则不需要上传和删除
        if (Storage::getAdapter() instanceof LocalFilesystemAdapter) {
            return;
        }

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
