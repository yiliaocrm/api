<?php

namespace App\Exports;

use App\Models\GoodsType;
use App\Models\InventoryDetail;

// excel
use Illuminate\Http\Request;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Illuminate\Contracts\Support\Responsable;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;

class InventoryDetailExport implements Responsable, WithColumnWidths, WithHeadings, FromQuery, WithMapping, WithStrictNullComparison
{
    use Exportable;

    private string $fileName = '库存变动明细表.xlsx';

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
        return InventoryDetail::query()
            ->select('inventory_detail.*')
            ->join('goods', 'goods.id', '=', 'inventory_detail.goods_id')
            ->when($this->request->input('date_start') && $this->request->input('date_end'), function ($query) {
                $query->whereBetween('date', [
                    $this->request->input('date_start'),
                    $this->request->input('date_end')
                ]);
            })
            ->when($this->request->input('goods_name'), function ($query) {
                $query->where('goods_name', 'like', '%' . $this->request->input('goods_name') . '%');
            })
            ->when($this->request->input('goods_type_id'), function ($query) {
                $query->whereIn('goods.type_id', GoodsType::query()->find($this->request->input('goods_type_id'))->getAllChild()->pluck('id'));
            })
            ->when($this->request->input('detailable_type'), function ($query) {
                $query->where('detailable_type', $this->request->input('detailable_type'));
            })
            ->when($this->request->input('warehouse_id'), function ($query) {
                $query->where('warehouse_id', $this->request->input('warehouse_id'));
            })
            ->orderBy('id', 'desc');
    }

    public function map($row): array
    {
        $type = config('setting.inventory_detail.detailable_type');
        return [
            $row->id,
            $row->date,
            $row->key,
            $row->goods_id,
            $row->goods_name,
            $row->specs,
            get_warehouse_name($row->warehouse_id),
            $row->unit_name,
            $row->manufacturer_name,
            $row->production_date,
            $row->expiry_date,
            $row->batch_code,
            $row->sncode,
            $row->remark,
            $type[$row->detailable_type],
            $row->price,
            $row->number,
            $row->amount,
            $row->inventory_number,
            $row->inventory_amount
        ];
    }

    public function headings(): array
    {
        return [
            'ID',
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
            '结存数量',
            '结存总价'
        ];
    }

    public function columnWidths(): array
    {
        return [
            'B' => 12,
            'C' => 20,
            'E' => 35,
            'F' => 20,
            'I' => 30,
            'J' => 12,
            'K' => 12,
        ];
    }
}
