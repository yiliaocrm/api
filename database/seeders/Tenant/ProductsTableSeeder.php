<?php

namespace Database\Seeders\Tenant;

use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        Product::truncate();
        Product::create([
            'id'                  => 1,
            'name'                => '预收费用',
            'keyword'             => 'ysfy,yushoufeiyong,预收费用',
            'type_id'             => 2,
            'times'               => 1,
            'disabled'            => 0,
            'price'               => '0.00',
            'sales_price'         => '0.00',
            'expiration'          => 0,
            'department_id'       => 2,
            'deduct'              => 0,
            'deduct_department'   => 2,
            'expense_category_id' => 1,
            'commission'          => 1,
            'integral'            => 0,
            'remark'              => '系统保留项目'
        ]);
        Product::create([
            'id'                  => 2,
            'name'                => '购卡换券',
            'keyword'             => 'gkhq,goukahuanquan,购卡换券',
            'type_id'             => 2,
            'times'               => 1,
            'disabled'            => 1,
            'price'               => '0.00',
            'sales_price'         => '0.00',
            'expiration'          => 0,
            'department_id'       => 2,
            'deduct'              => 0,
            'deduct_department'   => 2,
            'expense_category_id' => 1,
            'commission'          => 1,
            'integral'            => 0,
            'remark'              => '系统保留项目'
        ]);
    }
}
