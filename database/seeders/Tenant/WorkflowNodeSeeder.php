<?php

namespace Database\Seeders\Tenant;

use App\Models\WorkflowNode;
use Illuminate\Database\Seeder;

class WorkflowNodeSeeder extends Seeder
{
    public function run(): void
    {
        WorkflowNode::query()->truncate();
        $nodes = [
            [
                'key' => 'wait',
                'name' => '等待',
                'icon' => 'el-icon-timer',
                'color' => '#f1bc5f',
                'description' => '等待节点 - 暂停工作流等待指定时间后继续执行。',
                'dsl' => [
                    'type' => 'n8n-nodes-base.wait',
                    'category' => 'flow',
                    'displayName' => '等待',
                ],
                'template' => [
                    'type' => 'n8n-nodes-base.wait',
                    'typeVersion' => 1,
                    'parameters' => [
                        'amount' => 1,
                        'unit' => 'hours',
                    ],
                ],
            ],
            [
                'key' => 'end',
                'name' => '结束',
                'icon' => 'sc-icon-square-fill',
                'color' => '#f56c6c',
                'description' => '结束节点 - 工作流执行的终点。',
                'dsl' => [],
                'template' => [],
            ],
        ];
        foreach ($nodes as $node) {
            WorkflowNode::query()->create($node);
        }
    }
}
