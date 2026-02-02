<?php

namespace Database\Seeders\Tenant;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class WorkflowEventSeeder extends Seeder
{
    /**
     * 工作流事件配置
     */
    protected array $events = [
        // 客户相关事件
        ['event' => 'customer.created', 'event_name' => '客户创建', 'category_name' => '客户管理'],
        ['event' => 'customer.updated', 'event_name' => '客户更新', 'category_name' => '客户管理'],

        // 预约相关事件
        ['event' => 'reservation.created', 'event_name' => '预约创建', 'category_name' => '预约管理'],
        ['event' => 'reservation.updated', 'event_name' => '预约更新', 'category_name' => '预约管理'],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('workflow_events')->truncate();

        $now = now();
        $data = [];

        foreach ($this->events as $event) {
            $data[] = array_merge($event, [
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        DB::table('workflow_events')->insert($data);
    }
}
