<?php

namespace Database\Seeders\Tenant;

use App\Models\WorkflowComponent;
use Illuminate\Database\Seeder;

class WorkflowComponentSeeder extends Seeder
{
    public function run(): void
    {
        WorkflowComponent::query()->truncate();
        $components = [
            [
                'key' => 'wait',
                'name' => '等待',
                'icon' => 'el-icon-timer',
                'color' => '#f1bc5f',
                'description' => '等待组件 - 暂停工作流等待指定时间后继续执行。',
                'template' => [
                    'type' => 'delay',
                    'parameters' => [
                        'mode' => 'after', // 'at' 表示指定时间，'after' 表示相对时间
                        'time' => null, // 指定时间（ISO8601 格式）
                        'delay' => 1, // 延迟时间数值
                        'unit' => 'minutes', // 时间单位：seconds/minutes/hours/days
                        'overwrite' => false, // 是否覆盖待处理消息
                    ],
                ],
            ],
        ];
        foreach ($components as $component) {
            WorkflowComponent::query()->create($component);
        }
    }
}
