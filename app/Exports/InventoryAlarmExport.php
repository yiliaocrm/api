<?php

namespace App\Exports;

use Throwable;
use App\Models\Goods;
use Vtiful\Kernel\Excel;
use App\Models\GoodsType;
use App\Models\ExportTask;
use Illuminate\Bus\Queueable;
use App\Events\Web\ExportCompleted;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Contracts\Queue\ShouldQueue;
use League\Flysystem\Local\LocalFilesystemAdapter;

class InventoryAlarmExport implements ShouldQueue
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
                '物品分类',
                '物品名称',
                '规格型号',
                '基本单位',
                '库存数量',
                '库存上限',
                '库存下限',
                '预警状态'
            ];
            $sheet->header($headers);

            // 设置列宽
            $sheet->setColumn('B:B', 40);
            $sheet->setColumn('C:C', 10);
            $sheet->setColumn('E:E', 10);
            $sheet->setColumn('F:F', 10);

            // 查询数据
            $query = $this->getQuery();

            // 分批处理数据并直接写入
            $query->chunk($this->chunkSize, function ($records) use ($sheet) {
                $batchData = [];
                foreach ($records as $row) {
                    $status = '库存正常';

                    if ($row->max && $row->max < $row->inventory_number) {
                        $status = '库存过剩';
                    }

                    if ($row->min && $row->min > $row->inventory_number) {
                        $status = '库存不足';
                    }

                    $batchData[] = [
                        $row->type_name,
                        $row->name,
                        $row->specs,
                        $row->base_unit,
                        $row->inventory_number,
                        $row->max,
                        $row->min,
                        $status
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
        $warehouse_id = $this->request['warehouse_id'] ?? null;
        $type_id      = $this->request['type_id'] ?? null;
        $name         = $this->request['name'] ?? null;
        $status       = $this->request['status'] ?? null;
        $filterable   = $this->request['filterable'] ?? null;

        return Goods::query()
            ->select([
                'goods.id',
                'goods.name',
                'goods.specs',
                'goods_type.name as type_name',
                'unit.name as base_unit',
            ])
            ->leftJoin('goods_unit', function (JoinClause $join) {
                $join->on('goods_unit.goods_id', '=', 'goods.id')->where('basic', 1);
            })
            ->leftJoin('unit', 'unit.id', '=', 'goods_unit.unit_id')
            ->leftJoin('goods_type', 'goods_type.id', '=', 'goods.type_id')
            // 合计预警
            ->when(!$warehouse_id, function (Builder $query) {
                $query->addSelect(['goods.max', 'goods.min', 'goods.inventory_number']);
            })
            // 分仓预警
            ->when($warehouse_id, function (Builder $query) use ($warehouse_id) {
                $query->addSelect(['warehouse_alarm.max', 'warehouse_alarm.min'])
                    ->selectRaw('IFNULL(cy_inventory.number, 0) as inventory_number')
                    ->leftJoin('inventory', function (JoinClause $join) use ($warehouse_id) {
                        $join->on('inventory.goods_id', '=', 'goods.id')->where('inventory.warehouse_id', $warehouse_id);
                    })
                    ->leftJoin('warehouse_alarm', function (JoinClause $join) use ($warehouse_id) {
                        $join->on('warehouse_alarm.goods_id', '=', 'goods.id')->where('warehouse_alarm.warehouse_id', $warehouse_id);
                    });
            })
            ->when($type_id && $type_id != 1, function (Builder $query) use ($type_id) {
                $query->whereIn('goods.type_id', GoodsType::query()->find($type_id)->getAllChild()->pluck('id'));
            })
            ->when($name, function (Builder $query) use ($name) {
                $query->where('goods.name', 'like', '%' . $name . '%');
            })
            // 预警状态:库存正常
            ->when($status == 'normal', function (Builder $query) use ($warehouse_id) {
                $warehouse_id
                    ?
                    $query->where(function (Builder $query) {
                        $query->whereBetween('inventory.number', [DB::raw('cy_warehouse_alarm.min'), DB::raw('cy_warehouse_alarm.max')])
                            ->orWhere(function (Builder $query) {
                                $query->where('warehouse_alarm.max', 0)->where('inventory.number', '>=', DB::raw('cy_warehouse_alarm.min'));
                            })
                            ->orWhere(function (Builder $query) {
                                $query->where('warehouse_alarm.min', 0)->where('inventory.number', '<=', DB::raw('cy_warehouse_alarm.max'));
                            });
                    })
                    :
                    $query->where(function (Builder $query) {
                        $query->whereBetween('goods.inventory_number', [DB::raw('cy_goods.min'), DB::raw('cy_goods.max')])
                            ->orWhere(function (Builder $query) {
                                $query->where('goods.max', 0)->where('goods.inventory_number', '>=', DB::raw('cy_goods.min'));
                            })
                            ->orWhere(function (Builder $query) {
                                $query->where('goods.min', 0)->where('goods.inventory_number', '<=', DB::raw('cy_goods.max'));
                            });
                    });
            })
            // 预警状态:库存过剩
            ->when($status == 'high', function (Builder $query) use ($warehouse_id) {
                $warehouse_id
                    ? $query->where('warehouse_alarm.max', '<>', 0)->where('warehouse_alarm.max', '<', DB::raw('cy_inventory.number'))
                    : $query->where('goods.max', '<>', 0)->where('goods.max', '<', DB::raw('inventory_number'));
            })
            // 预警状态:库存不足
            ->when($status == 'low', function (Builder $query) use ($warehouse_id) {
                $warehouse_id
                    ? $query->where('warehouse_alarm.min', '<>', 0)->where('warehouse_alarm.min', '>', DB::raw('cy_inventory.number'))
                    : $query->where('goods.min', '<>', 0)->where('goods.min', '>', DB::raw('inventory_number'));
            })
            // 过滤库存为空
            ->when($filterable == 'hide', function (Builder $query) use ($warehouse_id) {
                $warehouse_id
                    ? $query->where('inventory.number', '>', 0)
                    : $query->where('goods.inventory_number', '>', 0);
            })
            ->orderBy('goods.id');
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
