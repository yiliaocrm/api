<?php

namespace App\Exports;

use App\Models\Goods;
use App\Models\GoodsType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\JoinClause;

// excel
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Illuminate\Contracts\Support\Responsable;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;

class InventoryAlarmExport implements Responsable, WithColumnWidths, WithHeadings, FromQuery, WithMapping, WithStrictNullComparison
{
    use Exportable;

    private string $fileName = '库存预警.xlsx';

    /**
     * @var $request Request
     */
    protected Request $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function query()
    {
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
            ->when(!$this->request->input('warehouse_id'), function (Builder $query) {
                $query->addSelect(['goods.max', 'goods.min', 'goods.inventory_number']);
            })
            // 分仓预警
            ->when($this->request->input('warehouse_id'), function (Builder $query) {
                $query->addSelect(['warehouse_alarm.max', 'warehouse_alarm.min'])
                    ->selectRaw('IFNULL(cy_inventory.number, 0) as inventory_number')
                    ->leftJoin('inventory', function (JoinClause $join) {
                        $join->on('inventory.goods_id', '=', 'goods.id')->where('inventory.warehouse_id', $this->request->input('warehouse_id'));
                    })
                    ->leftJoin('warehouse_alarm', function (JoinClause $join) {
                        $join->on('warehouse_alarm.goods_id', '=', 'goods.id')->where('warehouse_alarm.warehouse_id', $this->request->input('warehouse_id'));
                    });
            })
            ->when($this->request->input('type_id') && $this->request->input('type_id') != 1, function (Builder $query) {
                $query->whereIn('goods.type_id', GoodsType::query()->find($this->request->input('type_id'))->getAllChild()->pluck('id'));
            })
            ->when($this->request->input('name'), function (Builder $query) {
                $query->where('goods.name', 'like', '%' . $this->request->input('name') . '%');
            })
            // 预警状态:库存正常
            ->when($this->request->input('status') && $this->request->input('status') == 'normal', function (Builder $query) {
                $this->request->input('warehouse_id')
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
            ->when($this->request->input('status') && $this->request->input('status') == 'high', function (Builder $query) {
                $this->request->input('warehouse_id')
                    ? $query->where('warehouse_alarm.max', '<>', 0)->where('warehouse_alarm.max', '<', DB::raw('cy_inventory.number'))
                    : $query->where('goods.max', '<>', 0)->where('goods.max', '<', DB::raw('inventory_number'));
            })
            // 预警状态:库存不足
            ->when($this->request->input('status') && $this->request->input('status') == 'low', function (Builder $query) {
                $this->request->input('warehouse_id')
                    ? $query->where('warehouse_alarm.min', '<>', 0)->where('warehouse_alarm.min', '>', DB::raw('cy_inventory.number'))
                    : $query->where('goods.min', '<>', 0)->where('goods.min', '>', DB::raw('inventory_number'));
            })
            // 过滤库存为空
            ->when($this->request->input('filterable') && $this->request->input('filterable') == 'hide', function (Builder $query) {
                $this->request->input('warehouse_id')
                    ? $query->where('inventory.number', '>', 0)
                    : $query->where('goods.inventory_number', '>', 0);
            });
    }

    public function map($row): array
    {
        $status = '库存正常';

        if ($row->max && $row->max < $row->inventory_number) {
            $status = '库存过剩';
        }

        if ($row->max && $row->max > $row->inventory_number) {
            $status = '库存不足';
        }

        return [
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

    public function headings(): array
    {
        return [
            '物品分类',
            '物品名称',
            '规格型号',
            '基本单位',
            '库存数量',
            '库存上限',
            '库存下限',
            '预警状态'
        ];
    }

    public function columnWidths(): array
    {
        return [
            'B' => 40,
            'C' => 10,
            'E' => 10,
            'F' => 10,
            'I' => 30,
            'J' => 12,
            'K' => 12,
        ];
    }
}
