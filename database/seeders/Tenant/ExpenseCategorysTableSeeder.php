<?php

namespace Database\Seeders\Tenant;

use App\Models\ExpenseCategory;
use Illuminate\Database\Seeder;

class ExpenseCategorysTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        ExpenseCategory::query()->truncate();
        ExpenseCategory::query()->create(['name' => '其他费']);
        ExpenseCategory::query()->create(['name' => '手术费']);
        ExpenseCategory::query()->create(['name' => '西药费']);
        ExpenseCategory::query()->create(['name' => '中成药']);
        ExpenseCategory::query()->create(['name' => '中草药']);
        ExpenseCategory::query()->create(['name' => '化验费']);
        ExpenseCategory::query()->create(['name' => '检查费']);
        ExpenseCategory::query()->create(['name' => '诊查费']);
        ExpenseCategory::query()->create(['name' => '治疗费']);
        ExpenseCategory::query()->create(['name' => '修复费']);
        ExpenseCategory::query()->create(['name' => '物品费']);
    }
}
