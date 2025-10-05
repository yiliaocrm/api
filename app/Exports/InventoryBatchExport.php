<?php

namespace App\Exports;

use App\Models\InventoryBatchs;

// excel
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Illuminate\Contracts\Support\Responsable;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;

class InventoryBatchExport implements Responsable, WithColumnWidths, WithHeadings, FromQuery, WithMapping, WithStrictNullComparison
{
    use Exportable;

    private string $fileName = '库存批次明细表.xlsx';

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
        $keyword = $this->request->input('keyword');
        $sort    = $this->request->input('sort', 'id');
        $order   = $this->request->input('order', 'desc');
        return InventoryBatchs::query()
            ->with([
                'warehouse:id,name',
            ])
            ->select([
                'inventory_batchs.*'
            ])
            ->when($keyword, fn(Builder $query) => $query->where('inventory_batchs.goods_name', 'like', "%{$keyword}%"))
            ->queryConditions('InventoryBatchsIndex')
            ->orderBy("inventory_batchs.{$sort}", $order);
    }

    public function map($row): array
    {
        return [
            $row->goods_id,
            $row->goods_name,
            $row->manufacturer_name,
            $row->batch_code,
            $row->warehouse->name,
            $row->specs,
            $row->number,
            $row->unit_name,
            $row->price,
            $row->amount,
            $row->production_date,
            $row->expiry_date,
            $row->created_at,
        ];
    }

    public function headings(): array
    {
        return [
            '物品ID',
            '商品名称',
            '生产厂家',
            '批号',
            '所在仓库',
            '商品规格',
            '库存数量',
            '商品单位',
            '商品单价',
            '库存成本',
            '生产日期',
            '过期时间',
            '入库日期',
        ];
    }

    public function columnWidths(): array
    {
        return [
            'B' => 40,
            'C' => 40,
            'D' => 20,
            'E' => 15,
            'F' => 20,
            'I' => 20,
            'J' => 12,
            'K' => 12,
        ];
    }
}
