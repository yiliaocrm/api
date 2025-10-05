<?php

namespace Database\Seeders\Tenant;

use App\Models\SmsScenario;
use Illuminate\Database\Seeder;

class SmsScenarioSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        SmsScenario::query()->truncate();

        $common = [
            'customer_name'     => '客户姓名',
            'customer_birthday' => '客户生日',
        ];

        $scenarios = [
            [
                'name'      => '预约挂号',
                'scenario'  => 'appointment',
                'variables' => [
                    ...$common,
                    'appointment_date'  => '预约日期',
                    'appointment_start' => '预约开始时间',
                    'appointment_end'   => '预约结束时间',
                ],
            ],
            [
                'name'      => '治疗划扣',
                'scenario'  => 'treatment',
                'variables' => [
                    ...$common,
                    'treatment_date' => '治疗日期',
                    'treatment_time' => '治疗时间',
                ],
            ]
        ];
        foreach ($scenarios as $scenario) {
            SmsScenario::query()->create(array_merge($scenario, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }
}
