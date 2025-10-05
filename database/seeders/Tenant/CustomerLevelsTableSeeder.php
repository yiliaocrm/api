<?php

namespace Database\Seeders\Tenant;

use App\Models\CustomerLevel;
use Illuminate\Database\Seeder;

class CustomerLevelsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        CustomerLevel::truncate();
        CustomerLevel::create(['name' => '普通会员']);
    }
}
