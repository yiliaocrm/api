<?php

namespace Database\Seeders\Tenant;

use App\Models\ReservationType;
use Illuminate\Database\Seeder;

class ReservationTypesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        ReservationType::truncate();
        ReservationType::create(['id' => 1, 'name' => '网络咨询',   'remark' => '系统自带']);
        ReservationType::create(['id' => 2, 'name' => '电话咨询',   'remark' => '系统自带']);
        ReservationType::create(['id' => 3, 'name' => '新媒体',   'remark' => '系统自带']);
    }
}
