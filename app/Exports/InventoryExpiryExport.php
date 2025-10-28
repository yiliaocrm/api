<?php

namespace App\Exports;

use Carbon\Carbon;
use Throwable;
use App\Models\GoodsType;
use Vtiful\Kernel\Excel;
use App\Models\ExportTask;
use Illuminate\Bus\Queueable;
use App\Models\InventoryBatchs;
use App\Events\Web\ExportCompleted;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Contracts\Queue\ShouldQueue;
use League\Flysystem\Local\LocalFilesystemAdapter;

class InventoryExpiryExport implements ShouldQueue
{
    use Queueable;

    protected ExportTask $task;
    protected array $request;
    protected int $user_id;
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
            $path = Storage::disk('public')->path(dirname($this->task->file_path));

            // 确保目录存在
            if (!is_dir($path)) {
                mkdir($path, 0755, true);
            }

            // 初始化 xlswriter
            $excel = new Excel(['path' => $path]);

            // 设置导出文件名
            $sheet = $excel->constMemory(basename($this->task->file_path), 'Sheet1', false);

            // 设置表头
            $headers = [
                '所在仓库',
                '物品名称',
                '规格型号',
                '生产厂家',
                '批号',
                '库存数量',
                '商品单位',
                '预警天数',
                '预警状态',
                '生产日期',
                '剩余天数',
                '过期时间',
                '入库日期'
            ];
            $sheet->header($headers);

            // 设置列宽
            $sheet->setColumn('B:B', 40);
            $sheet->setColumn('C:C', 20);
            $sheet->setColumn('D:D', 25);
            $sheet->setColumn('E:E', 15);
            $sheet->setColumn('F:F', 10);
            $sheet->setColumn('I:I', 15);
            $sheet->setColumn('J:J', 12);
            $sheet->setColumn('K:K', 10);

            // 查询数据
            $query = $this->getQuery();

            // 分批处理数据并直接写入
            $query->chunk($this->chunkSize, function ($records) use ($sheet) {
                $batchData = [];
                foreach ($records as $row) {
                    $status = '正常';

                    if (Carbon::parse($row->expiry_date)->isBefore(Carbon::today())) {
                        $status = '已经过期';
                    }

                    $end   = Carbon::parse($row->expiry_date)->toDate();
                    $start = Carbon::parse($row->expiry_date)->subDays($row->warn_days)->toDate();

                    if ($row->warn_days && Carbon::today()->isBetween($start, $end)) {
                        $status = '预警期内';
                    }

                    $batchData[] = [
                        $row->warehouse_name,
                        $row->goods_name,
                        $row->specs,
                        $row->manufacturer_name,
                        $row->batch_code,
                        $row->number,
                        $row->unit_name,
                        $row->warn_days,
                        $status,
                        $row->production_date,
                        $row->expiry_diff,
                        $row->expiry_date,
                        $row->created_at
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

            // 触发导出完成事件,通知前端
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
        $type_id      = $this->request['type_id'] ?? null;
        $name         = $this->request['name'] ?? null;
        $warehouse_id = $this->request['warehouse_id'] ?? null;
        $status       = $this->request['status'] ?? null;
        $expiry_diff  = $this->request['expiry_diff'] ?? null;

        return InventoryBatchs::query()
            ->select([
                'warehouse.name as warehouse_name',
                'inventory_batchs.goods_name',
                'inventory_batchs.specs',
                'inventory_batchs.manufacturer_name',
                'inventory_batchs.batch_code',
                'inventory_batchs.number',
                'inventory_batchs.unit_name',
                'goods.warn_days',
                'inventory_batchs.production_date',
                'inventory_batchs.expiry_date',
                'inventory_batchs.created_at',
            ])
            ->addSelect(DB::raw('DATEDIFF(cy_inventory_batchs.expiry_date, curdate()) as expiry_diff'))
            ->leftJoin('warehouse', 'warehouse.id', '=', 'inventory_batchs.warehouse_id')
            ->leftJoin('goods', 'goods.id', '=', 'inventory_batchs.goods_id')
            ->when($type_id && $type_id != 1, function (Builder $query) use ($type_id) {
                $query->whereIn('goods.type_id', GoodsType::query()->find($type_id)->getAllChild()->pluck('id'));
            })
            ->where('inventory_batchs.number', '>', 0)
            ->whereNotNull('inventory_batchs.expiry_date')
            ->when($name, function (Builder $query) use ($name) {
                $query->where('goods.name', 'like', '%' . $name . '%');
            })
            ->when($warehouse_id, function (Builder $query) use ($warehouse_id) {
                $query->where('inventory_batchs.warehouse_id', $warehouse_id);
            })
            // 正常
            ->when($status == 'normal', function (Builder $query) {
                $query->where('inventory_batchs.expiry_date', '>=', DB::raw('curdate()'))
                    ->whereNotBetween(DB::raw('curdate()'), [
                        DB::raw('DATE_SUB(cy_inventory_batchs.expiry_date, INTERVAL cy_goods.warn_days DAY)'),
                        DB::raw('cy_inventory_batchs.expiry_date')
                    ]);
            })
            // 预警期内
            ->when($status == 'expiring', function (Builder $query) {
                $query->where('goods.warn_days', '<>', 0)
                    ->whereBetween(DB::raw('curdate()'), [
                        DB::raw('DATE_SUB(cy_inventory_batchs.expiry_date, INTERVAL cy_goods.warn_days DAY)'),
                        DB::raw('cy_inventory_batchs.expiry_date')
                    ]);
            })
            // 已经过期
            ->when($status == 'expired', function (Builder $query) {
                $query->where('inventory_batchs.expiry_date', '<', DB::raw('curdate()'));
            })
            // 剩余天数
            ->when($expiry_diff, function (Builder $query) use ($expiry_diff) {
                $query->whereRaw('DATEDIFF(cy_inventory_batchs.expiry_date, curdate()) <= ?', $expiry_diff);
            })
            ->orderBy('inventory_batchs.id', 'desc');
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
     * 如果不是本地存储,则上传到云端并删除本地文件
     */
    protected function uploadToCloudAndDeleteLocalFile(): void
    {
        // 如果使用的是本地存储,则不需要上传和删除
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
