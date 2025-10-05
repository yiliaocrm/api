<?php

namespace Database\Seeders\Tenant;

use App\Models\Store;
use Illuminate\Database\Seeder;

class StoreSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        Store::query()->truncate();
        Store::query()->create([
            'id'                       => 1,
            'name'                     => '总店',
            'short_name'               => '总部',
            'business_start'           => '09:00:00',
            'business_end'             => '20:00:00',
            'remark'                   => '系统自动生成,无法删除!',
            'appointment_color_scheme' => 'default',
            'appointment_color_config' => [
                [
                    'name'  => '3.0配色方案',
                    'value' => 'default',
                    'data'  => [
                        [
                            'name'  => '待确认',
                            'color' => '#f9dd48',
                        ],
                        [
                            'name'  => '待上门',
                            'color' => '#6eec68',
                        ],
                        [
                            'name'  => '已到店',
                            'color' => '#61daec',
                        ],
                        [
                            'name'  => '已接待',
                            'color' => '#f7a16e',
                        ],
                        [
                            'name'  => '已收费',
                            'color' => '#8f8cfa',
                        ],
                        [
                            'name'  => '已治疗',
                            'color' => '#caf4fe',
                        ],
                        [
                            'name'  => '已超时',
                            'color' => '#e4e5e7',
                        ],
                        [
                            'name'  => '已离开',
                            'color' => '#969eba',
                        ]
                    ]
                ],
                [
                    'name'  => '经典配色方案',
                    'value' => 'classic',
                    'data'  => [
                        [
                            'name'  => '待确认',
                            'color' => '#66bafb',
                        ],
                        [
                            'name'  => '待上门',
                            'color' => '#3a78d7',
                        ],
                        [
                            'name'  => '已到店',
                            'color' => '#284894',
                        ],
                        [
                            'name'  => '已接待',
                            'color' => '#f5c649',
                        ],
                        [
                            'name'  => '已收费',
                            'color' => '#46c883',
                        ],
                        [
                            'name'  => '已治疗',
                            'color' => '#a0a0a0',
                        ],
                        [
                            'name'  => '已超时',
                            'color' => '#8ad245',
                        ],
                        [
                            'name'  => '已离开',
                            'color' => '#808080',
                        ]
                    ]
                ],
                [
                    'name'  => '自定义配色方案',
                    'value' => 'custom',
                    'data'  => [
                        [
                            'name'  => '待确认',
                            'color' => '#498cf9',
                        ],
                        [
                            'name'  => '待上门',
                            'color' => '#271999',
                        ],
                        [
                            'name'  => '已到店',
                            'color' => '#b9ec97',
                        ],
                        [
                            'name'  => '已接待',
                            'color' => '#5cc53b',
                        ],
                        [
                            'name'  => '已收费',
                            'color' => '#165204',
                        ],
                        [
                            'name'  => '已治疗',
                            'color' => '#fad798',
                        ],
                        [
                            'name'  => '已超时',
                            'color' => '#f18f33',
                        ],
                        [
                            'name'  => '已离开',
                            'color' => '#c3c8cc',
                        ],
                    ]
                ]
            ]
        ]);
    }
}
