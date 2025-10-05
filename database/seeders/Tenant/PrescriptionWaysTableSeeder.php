<?php

namespace Database\Seeders\Tenant;

use App\Models\PrescriptionWays;
use Illuminate\Database\Seeder;

class PrescriptionWaysTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        PrescriptionWays::truncate();

        PrescriptionWays::create(['name' => '口服']);
        PrescriptionWays::create(['name' => '外用']);
        PrescriptionWays::create(['name' => '静脉滴注']);
        PrescriptionWays::create(['name' => '肌肉注射']);
        PrescriptionWays::create(['name' => '静脉注射']);
        PrescriptionWays::create(['name' => '皮下注射']);
        PrescriptionWays::create(['name' => '腹腔注射']);
        PrescriptionWays::create(['name' => '腹腔注射']);
        PrescriptionWays::create(['name' => '直肠给药']);
        PrescriptionWays::create(['name' => '舌下给药']);

        // 中药
        PrescriptionWays::create(['name' => '后下', 'type' => 2]);
        PrescriptionWays::create(['name' => '布包', 'type' => 2]);
        PrescriptionWays::create(['name' => '打碎', 'type' => 2]);
        PrescriptionWays::create(['name' => '包煎', 'type' => 2]);
        PrescriptionWays::create(['name' => '烊化', 'type' => 2]);
        PrescriptionWays::create(['name' => '先煎', 'type' => 2]);
    }
}
