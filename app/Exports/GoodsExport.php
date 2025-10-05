<?php

namespace App\Exports;

use App\Models\Goods;
use App\Models\GoodsType;

// excel
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Illuminate\Contracts\Support\Responsable;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;

class GoodsExport implements Responsable, WithColumnWidths, WithHeadings, FromQuery, WithMapping, WithStrictNullComparison
{
	use Exportable;

	private string $fileName = '商品信息.xlsx';

	public function query()
	{
		return Goods::query()->when(request('type_id') && request('type_id') !=1, function($query) {
			$query->whereIn('type_id', GoodsType::find(request('type_id'))->getAllChild()->pluck('id'));
		})
		->when(request('keyword'), function($query) {
			$query->where('keyword', 'like', '%' . request('keyword') . '%');
		})
		->when(request('warn_days_start') && request('warn_days_end'), function($query) {
			$query->whereBetween('warn_days', [
				request('warn_days_start'),
				request('warn_days_end')
			]);
		})
		->when(request('inventory_number_start') && request('inventory_number_end'), function($query) {
			$query->whereBetween('inventory_number', [
				request('inventory_number_start'),
				request('inventory_number_end')
			]);
		})
		->when(request('inventory_amount_start') && request('inventory_amount_end'), function($query) {
			$query->whereBetween('inventory_amount', [
				request('inventory_amount_start'),
				request('inventory_amount_end')
			]);
		})
		->orderBy(request('sort', 'id'), request('order', 'desc'));
	}

	public function map($row): array
	{
		return [
			$row->id,
			$row->name,
			$row->short_name,
			get_goods_type_name($row->type_id),
			get_expense_category_name($row->expense_category_id),
            $row->high_value ? '是' : '否',
			$row->is_drug ? '是' : '否',
			$row->specs,
			$row->warn_days,
			$row->min,
			$row->max,
			$row->commission ? '是' : '否',
			$row->integral ? '是' : '否',
			$row->inventory_number,
			$row->inventory_amount,
			$row->disabled ? '停用' : '正常',
			$row->remark
		];
	}


	public function headings(): array
	{
		return [
			'项目编号',
			'商品名称',
			'商品简称',
			'商品类别',
			'费用类别',
            '高值耗材',
			'是否药品',
			'商品规格',
			'预警天数',
			'库存下限',
			'库存上限',
			'开单提成',
			'消费积分',
			'库存数量',
			'库存成本',
			'商品状态',
			'备注信息',
		];
	}

    public function columnWidths(): array
    {
        return [
            'A' => 10,
            'B' => 50,
            'C' => 50,
            'D' => 20,
        ];
    }
}
