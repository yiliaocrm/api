<?php

namespace Database\Seeders\Tenant;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class WorkflowComponentTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('workflow_component_types')->truncate();
        DB::table('workflow_component_types')->insert([
            [
                'name' => '开始节点',
                'key' => 'start',
                'icon' => 'el-icon-video-play',
                'bg_color' => '#2563EB',
                'description' => '添加工作流的启动方式',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => '流程控制',
                'key' => 'flow_control',
                'icon' => 'el-icon-switch',
                'bg_color' => '#10B981',
                'description' => '控制工作流的执行流程',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => '条件判断',
                'key' => 'condition',
                'icon' => 'el-icon-share',
                'bg_color' => '#F59E0B',
                'description' => '根据条件进行分支判断',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => '任务触达',
                'key' => 'task_delivery',
                'icon' => 'sc-icon-target',
                'bg_color' => '#10B981',
                'description' => '自动创建任务和触达客户',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
