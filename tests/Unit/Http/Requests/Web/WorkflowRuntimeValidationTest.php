<?php

namespace Tests\Unit\Http\Requests\Web;

use App\Http\Requests\Web\WorkflowRequest;
use Tests\TestCase;

class WorkflowRuntimeValidationTest extends TestCase
{
    public function test_validate_rule_chain_accepts_create_followup_node(): void
    {
        $request = new WorkflowRequest;

        $result = $request->validateRuleChainForRuntime([
            'nodes' => [
                ['id' => 'start', 'type' => 'start_trigger'],
                [
                    'id' => 'followup_1',
                    'type' => 'create_followup',
                    'parameters' => [
                        'title' => '术后回访',
                        'type' => 1,
                        'tool' => 2,
                        'followup_user' => 1001,
                        'date_mode' => 'relative',
                        'date_offset' => 1,
                        'date_unit' => 'days',
                    ],
                ],
                ['id' => 'end', 'type' => 'end'],
            ],
            'connections' => [
                ['source' => 'start', 'target' => 'followup_1', 'type' => 'main'],
                ['source' => 'followup_1', 'target' => 'end', 'type' => 'main'],
            ],
        ]);

        $this->assertTrue($result['valid']);
        $this->assertSame('ok', $result['message']);
    }

    public function test_validate_rule_chain_accepts_create_followup_node_with_zero_date_offset(): void
    {
        $request = new WorkflowRequest;

        $result = $request->validateRuleChainForRuntime([
            'nodes' => [
                ['id' => 'start', 'type' => 'start_trigger'],
                [
                    'id' => 'followup_1',
                    'type' => 'create_followup',
                    'parameters' => [
                        'title' => '术后回访',
                        'type' => 1,
                        'tool' => 2,
                        'followup_user' => 1001,
                        'date_mode' => 'relative',
                        'date_offset' => 0,
                        'date_unit' => 'days',
                    ],
                ],
                ['id' => 'end', 'type' => 'end'],
            ],
            'connections' => [
                ['source' => 'start', 'target' => 'followup_1', 'type' => 'main'],
                ['source' => 'followup_1', 'target' => 'end', 'type' => 'main'],
            ],
        ]);

        $this->assertTrue($result['valid']);
        $this->assertSame('ok', $result['message']);
    }

    public function test_validate_rule_chain_rejects_create_followup_node_with_negative_date_offset(): void
    {
        $request = new WorkflowRequest;

        $result = $request->validateRuleChainForRuntime([
            'nodes' => [
                ['id' => 'start', 'type' => 'start_trigger'],
                [
                    'id' => 'followup_1',
                    'type' => 'create_followup',
                    'parameters' => [
                        'title' => '术后回访',
                        'type' => 1,
                        'tool' => 2,
                        'followup_user' => 1001,
                        'date_mode' => 'relative',
                        'date_offset' => -1,
                        'date_unit' => 'days',
                    ],
                ],
                ['id' => 'end', 'type' => 'end'],
            ],
            'connections' => [
                ['source' => 'start', 'target' => 'followup_1', 'type' => 'main'],
                ['source' => 'followup_1', 'target' => 'end', 'type' => 'main'],
            ],
        ]);

        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('必须大于等于 0', $result['message']);
    }

    public function test_validate_rule_chain_rejects_create_followup_without_followup_user(): void
    {
        $request = new WorkflowRequest;

        $result = $request->validateRuleChainForRuntime([
            'nodes' => [
                ['id' => 'start', 'type' => 'start_trigger'],
                [
                    'id' => 'followup_1',
                    'type' => 'create_followup',
                    'parameters' => [
                        'title' => '术后回访',
                        'type' => 1,
                        'tool' => 2,
                        'date_mode' => 'relative',
                        'date_offset' => 1,
                        'date_unit' => 'days',
                    ],
                ],
                ['id' => 'end', 'type' => 'end'],
            ],
            'connections' => [
                ['source' => 'start', 'target' => 'followup_1', 'type' => 'main'],
                ['source' => 'followup_1', 'target' => 'end', 'type' => 'main'],
            ],
        ]);

        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('缺少提醒人员', $result['message']);
    }
}
