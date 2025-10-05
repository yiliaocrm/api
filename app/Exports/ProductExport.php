<?php

namespace App\Exports;

use App\Models\Product;

// excel
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Illuminate\Contracts\Support\Responsable;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;

class ProductExport implements Responsable, WithColumnWidths, WithHeadings, FromQuery, WithMapping, WithStrictNullComparison
{
    use Exportable;

    private string $fileName = '收费项目.xlsx';

    public function query()
    {
        return Product::query()
            ->orderBy('id', 'desc');
    }

    public function map($row): array
    {
        return [
            $row->id,
            $row->name,
            $row->print_name,
            get_product_type_name($row->type_id),
            $row->times,
            $row->price,
            $row->sales_price,
            $row->disabled ? '停用' : '启用',
            $row->specs,
            $row->expiration,
            get_expense_category_name($row->expense_category_id),
            get_department_name($row->department_id),
            get_department_name($row->deduct_department),
            $row->deduct ? '是' : '否',
            $row->commission ? '是' : '否',
            $row->integral ? '是' : '否',
            $row->successful ? '是' : '否',
            $row->remark,
        ];
    }


    public function headings(): array
    {
        return [
            '项目编号',
            '项目名称',
            '打印名称',
            '项目类别',
            '项目次数',
            '项目原价',
            '执行价格',
            '项目状态',
            '项目规格',
            '使用期限',
            '费用类别',
            '结算科室',
            '划扣科室',
            '需要划扣',
            '开单提成',
            '消费积分',
            '统计成交',
            '项目备注',
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 10,
            'B' => 50,
            'C' => 15,
            'D' => 10,
            'E' => 10,
            'F' => 15,
        ];
    }
}
