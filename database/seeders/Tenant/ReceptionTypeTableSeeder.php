<?php

namespace Database\Seeders\Tenant;

use App\Models\ReceptionType;
use Illuminate\Database\Seeder;

class ReceptionTypeTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        ReceptionType::truncate();
        ReceptionType::create(['id' => 1, 'name' => '初诊',   'remark' => '系统自带']);
        ReceptionType::create(['id' => 2, 'name' => '复诊',   'remark' => '系统自带']);
        ReceptionType::create(['id' => 3, 'name' => '再消费', 'remark' => '系统自带']);
        ReceptionType::create(['id' => 4, 'name' => '复查',   'remark' => '系统自带']);
        ReceptionType::create(['id' => 5, 'name' => '其他',   'remark' => '系统自带']);
    }
}
