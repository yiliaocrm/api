<?php

namespace Database\Seeders\Tenant;

use App\Models\Failure;
use Illuminate\Database\Seeder;

class FailuresTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        Failure::query()->truncate();
        Failure::query()->create(['name' => '未成交原因', 'parentid' => 0]);
        Failure::query()->create(['name' => '价格高', 'parentid' => 0]);
        Failure::query()->create(['name' => '对比同行', 'parentid' => 0]);
        Failure::query()->create(['name' => '回去考虑', 'parentid' => 0]);
        Failure::query()->create(['name' => '需要时间安排', 'parentid' => 0]);
        Failure::query()->create(['name' => '身体不佳/不合格', 'parentid' => 0]);
        Failure::query()->create(['name' => '和家人商量', 'parentid' => 0]);
        Failure::query()->create(['name' => '其他', 'parentid' => 0]);
    }
}
