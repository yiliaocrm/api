<?php

namespace Database\Seeders\Tenant;

use App\Models\CustomerEconomic;
use Illuminate\Database\Seeder;

class CustomerEconomicsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        CustomerEconomic::truncate();
        CustomerEconomic::create(['id' => 1, 'name' => '2000以下',   'remark' => '系统自带']);
        CustomerEconomic::create(['id' => 2, 'name' => '2000~5000',   'remark' => '系统自带']);
        CustomerEconomic::create(['id' => 3, 'name' => '5000~10000', 'remark' => '系统自带']);
        CustomerEconomic::create(['id' => 4, 'name' => '10000~2000', 'remark' => '系统自带']);
        CustomerEconomic::create(['id' => 5, 'name' => '20000以上', 'remark' => '系统自带']);
    }
}
