<?php

namespace App\Exports;

use App\Models\GoodsType;
use App\Models\Inventory;

// excel
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Illuminate\Contracts\Support\Responsable;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;

class InventoryExport implements Responsable, WithColumnWidths, WithHeadings, FromQuery, WithMapping, WithStrictNullComparison
{
    use Exportable;

    private string $fileName = '库存查询.xlsx';

    public function query()
    {
        $sort    = request('sort', 'id');
        $order   = request('order', 'desc');
        $keyword = request('keyword');
        return Inventory::query()
            ->with([
                'goods.type:id,name',
                'goods.units:id,basic,goods_id,unit_id',
                'goods.units.unit:id,name',
                'warehouse:id,name',
                'goods:id,name,type_id,specs',
            ])
            ->select([
                'inventory.*'
            ])
            ->join('goods', 'goods.id', '=', 'inventory.goods_id')
            ->whereIn('goods.type_id', GoodsType::query()->find(request('type_id'))->getAllChild()->pluck('id'))
            ->when($keyword, fn(Builder $query) => $query->where('goods.keyword', 'like', "%{$keyword}%"))
            ->queryConditions('InventoryIndex')
            ->orderBy("inventory.{$sort}", $order)
            ->orderBy('inventory.id');
    }

    public function map($row): array
    {
        return [
            $row->id,
            $row->goods->name,
            $row->goods->specs,
            $row->goods->type->name,
            $row->number,
            $row->basicUnit->unit->name,
            $row->amount,
            $row->warehouse->name,
        ];
    }

    public function headings(): array
    {
        return [
            'ID',
            '商品名称',
            '商品规格',
            '商品分类',
            '库存数量',
            '基本单位',
            '库存成本',
            '仓库名称',
        ];
    }

    public function columnWidths(): array
    {
        return [
            'B' => 45,
        ];
    }
}
