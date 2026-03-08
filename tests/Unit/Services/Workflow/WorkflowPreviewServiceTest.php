<?php

namespace Tests\Unit\Services\Workflow;

use App\Services\Workflow\WorkflowPreviewService;
use Tests\TestCase;

class WorkflowPreviewServiceTest extends TestCase
{
    public function test_infer_preview_input_merges_upstream_output_samples_by_flow_order(): void
    {
        $service = new WorkflowPreviewService;

        $ruleChain = [
            'nodes' => [
                ['id' => 'start', 'type' => 'start_trigger', 'name' => '开始'],
                ['id' => 'a', 'type' => 'log', 'name' => '节点A'],
                ['id' => 'b', 'type' => 'log', 'name' => '节点B'],
                ['id' => 'target', 'type' => 'if', 'name' => '目标节点'],
            ],
            'connections' => [
                ['source' => 'start', 'target' => 'a', 'type' => 'main'],
                ['source' => 'start', 'target' => 'b', 'type' => 'main'],
                ['source' => 'a', 'target' => 'target', 'type' => 'main'],
                ['source' => 'b', 'target' => 'target', 'type' => 'main'],
            ],
            'layout' => [
                'flow' => [
                    ['nodeId' => 'start', 'order' => 0],
                    ['nodeId' => 'a', 'order' => 1],
                    ['nodeId' => 'b', 'order' => 2],
                    ['nodeId' => 'target', 'order' => 3],
                ],
            ],
            'meta' => [
                'preview_schemas' => [
                    'start' => ['output_sample' => ['source' => 'start', 'shared' => 's0']],
                    'a' => ['output_sample' => ['from_a' => true, 'shared' => 's1']],
                    'b' => ['output_sample' => ['from_b' => true, 'shared' => 's2']],
                ],
            ],
        ];

        $inferred = $service->inferPreviewInput($ruleChain, 'target');

        $this->assertSame('start', $inferred['source']);
        $this->assertTrue($inferred['from_a']);
        $this->assertTrue($inferred['from_b']);
        $this->assertSame('s2', $inferred['shared']);
    }

    public function test_preview_node_supports_create_followup_node(): void
    {
        $service = new WorkflowPreviewService;

        $ruleChain = [
            'nodes' => [
                ['id' => 'start', 'type' => 'start_trigger', 'name' => '开始'],
                [
                    'id' => 'followup_1',
                    'type' => 'create_followup',
                    'name' => '创建回访',
                    'parameters' => [
                        'title' => '术后回访',
                        'type' => 1,
                        'tool' => 2,
                        'followup_user' => 99,
                        'date_mode' => 'relative',
                        'date_offset' => 2,
                        'date_unit' => 'days',
                    ],
                ],
            ],
            'connections' => [
                ['source' => 'start', 'target' => 'followup_1', 'type' => 'main'],
            ],
        ];

        $result = $service->previewNode($ruleChain, 'followup_1', ['id' => 'customer-001']);

        $this->assertSame('create_followup', $result['node_type']);
        $this->assertTrue((bool) ($result['output_data']['created'] ?? false));
        $this->assertNotEmpty($result['output_data']['followup_id'] ?? null);
        $this->assertSame('customer-001', $result['output_data']['customer_id'] ?? null);
        $this->assertNotEmpty($result['output_data']['followup_date'] ?? null);
    }

    public function test_preview_node_rejects_create_followup_without_customer_id(): void
    {
        $service = new WorkflowPreviewService;

        $ruleChain = [
            'nodes' => [
                ['id' => 'start', 'type' => 'start_trigger', 'name' => '开始'],
                [
                    'id' => 'followup_1',
                    'type' => 'create_followup',
                    'name' => '创建回访',
                    'parameters' => [
                        'title' => '术后回访',
                        'type' => 1,
                        'tool' => 2,
                        'followup_user' => 99,
                        'date_mode' => 'relative',
                        'date_offset' => 1,
                        'date_unit' => 'days',
                    ],
                ],
            ],
            'connections' => [
                ['source' => 'start', 'target' => 'followup_1', 'type' => 'main'],
            ],
        ];

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('缺少客户ID');

        $service->previewNode($ruleChain, 'followup_1', []);
    }
}
