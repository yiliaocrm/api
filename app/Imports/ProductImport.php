<?php

namespace App\Imports;

use App\Models\Product;
use App\Models\Department;
use App\Models\ProductType;
use App\Models\ExpenseCategory;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;

class ProductImport implements ToModel, WithHeadingRow, WithChunkReading, WithBatchInserts, WithValidation
{
    use Importable;

    /**
     * 数据模型
     * @param array $row
     * @return Product
     */
    public function model(array $row): Product
    {
        $type              = ProductType::query()->where('name', $row['项目类别'])->first();
        $category          = ExpenseCategory::query()->where('name', $row['费用类别'])->first();
        $department        = Department::query()->where('name', $row['结算科室'])->first();
        $deduct_department = Department::query()->where('name', $row['划扣科室'])->first();

        return new Product([
            'name'                => $row['项目名称'],
            'keyword'             => implode(',', parse_pinyin($row['项目名称'])),
            'type_id'             => $type->id,
            'times'               => $row['项目次数'],
            'price'               => $row['项目原价'],
            'sales_price'         => $row['执行价格'],
            'specs'               => $row['项目规格'],
            'expiration'          => $row['使用期限'],
            'department_id'       => $department->id,
            'deduct_department'   => $deduct_department->id,
            'deduct'              => ($row['需要划扣'] == '是') ? 1 : 0,
            'expense_category_id' => $category->id,
            'commission'          => ($row['开单提成'] == '是') ? 1 : 0,
            'integral'            => ($row['消费积分'] == '是') ? 1 : 0,
            'successful'          => ($row['统计成交'] == '是') ? 1 : 0,
            'remark'              => $row['项目备注']
        ]);
    }

    /**
     * 导入行验证规则
     * @return array
     */
    public function rules(): array
    {
        return [
            '项目名称' => 'required',
            '项目类别' => 'required|exists:product_type,name',
            '项目次数' => 'required|numeric',
            '项目原价' => 'required|numeric',
            '执行价格' => 'required|numeric',
            '项目规格' => 'nullable',
            '使用期限' => 'required|numeric',
            '费用类别' => 'required|exists:expense_category,name',
            '结算科室' => 'required|exists:department,name',
            '划扣科室' => 'required|exists:department,name',
            '需要划扣' => 'required|in:"是","否"',
            '开单提成' => 'required|in:"是","否"',
            '统计成交' => 'required|in:"是","否"',
            '消费积分' => 'required|in:"是","否"',
        ];
    }

    /**
     * 每次读取1000条数据
     * @return int
     */
    public function chunkSize(): int
    {
        return 1000;
    }

    /**
     * 1000条数据才进行一次写入操作
     * @return int
     */
    public function batchSize(): int
    {
        return 1000;
    }
}
