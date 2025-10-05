<?php

namespace Database\Seeders\Tenant;

use App\Models\ScheduleRule;
use Illuminate\Database\Seeder;

class ScheduleRulesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        ScheduleRule::query()->truncate();
        ScheduleRule::query()->create([
            'name'        => '休息',
            'start'       => '09:00',
            'end'         => '21:00',
            'color'       => '#a9a9a9',
            'onduty'      => false,
            'store_id' => 1
        ]);
        ScheduleRule::query()->create([
            'name'        => '正常班',
            'start'       => '09:00',
            'end'         => '21:00',
            'color'       => '#008000',
            'onduty'      => true,
            'store_id' => 1
        ]);
    }
}
