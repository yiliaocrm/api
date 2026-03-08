<?php

namespace Tests\Unit\Http\Requests\Web;

use App\Http\Requests\Web\WorkflowRequest;
use Tests\TestCase;

class WorkflowRequestPeriodicValidationTest extends TestCase
{
    public function test_validate_rule_chain_accepts_start_periodic_node(): void
    {
        $request = new WorkflowRequest;

        $result = $request->validateRuleChainForRuntime([
            'nodes' => [
                ['id' => 'start', 'type' => 'start_periodic'],
                ['id' => 'end', 'type' => 'end'],
            ],
            'connections' => [
                ['source' => 'start', 'target' => 'end', 'type' => 'main'],
            ],
        ]);

        $this->assertTrue($result['valid']);
        $this->assertSame('ok', $result['message']);
    }

    public function test_validate_rule_chain_rejects_multiple_start_nodes(): void
    {
        $request = new WorkflowRequest;

        $result = $request->validateRuleChainForRuntime([
            'nodes' => [
                ['id' => 'start1', 'type' => 'start_trigger'],
                ['id' => 'start2', 'type' => 'start_periodic'],
                ['id' => 'end', 'type' => 'end'],
            ],
            'connections' => [
                ['source' => 'start1', 'target' => 'end', 'type' => 'main'],
            ],
        ]);

        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('只能包含一个开始节点', $result['message']);
    }

    public function test_validate_rule_chain_rejects_missing_start_node(): void
    {
        $request = new WorkflowRequest;

        $result = $request->validateRuleChainForRuntime([
            'nodes' => [
                ['id' => 'log1', 'type' => 'log'],
                ['id' => 'end', 'type' => 'end'],
            ],
            'connections' => [
                ['source' => 'log1', 'target' => 'end', 'type' => 'main'],
            ],
        ]);

        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('只能包含一个开始节点', $result['message']);
    }

    public function test_validate_rule_chain_accepts_periodic_with_if_node(): void
    {
        $request = new WorkflowRequest;

        $result = $request->validateRuleChainForRuntime([
            'nodes' => [
                ['id' => 'start', 'type' => 'start_periodic'],
                [
                    'id' => 'if_1',
                    'type' => 'if',
                    'parameters' => [
                        'matchType' => 'all',
                        'rules' => [
                            [
                                'leftType' => 'path',
                                'leftValue' => 'payload.age',
                                'operator' => 'gte',
                                'rightType' => 'literal',
                                'rightValue' => 18,
                            ],
                        ],
                    ],
                ],
                ['id' => 'end_true', 'type' => 'end'],
                ['id' => 'end_false', 'type' => 'end'],
            ],
            'connections' => [
                ['source' => 'start', 'target' => 'if_1', 'type' => 'main'],
                ['source' => 'if_1', 'target' => 'end_true', 'type' => 'branch', 'sourcePort' => 'true'],
                ['source' => 'if_1', 'target' => 'end_false', 'type' => 'branch', 'sourcePort' => 'false'],
            ],
        ]);

        $this->assertTrue($result['valid']);
    }

    public function test_validate_rule_chain_accepts_periodic_with_wait_and_log(): void
    {
        $request = new WorkflowRequest;

        $result = $request->validateRuleChainForRuntime([
            'nodes' => [
                ['id' => 'start', 'type' => 'start_periodic'],
                ['id' => 'wait_1', 'type' => 'wait'],
                ['id' => 'log_1', 'type' => 'log'],
                ['id' => 'end', 'type' => 'end'],
            ],
            'connections' => [
                ['source' => 'start', 'target' => 'wait_1', 'type' => 'main'],
                ['source' => 'wait_1', 'target' => 'log_1', 'type' => 'main'],
                ['source' => 'log_1', 'target' => 'end', 'type' => 'main'],
            ],
        ]);

        $this->assertTrue($result['valid']);
        $this->assertSame('ok', $result['message']);
    }

    public function test_start_trigger_still_works_after_adding_periodic_support(): void
    {
        $request = new WorkflowRequest;

        $result = $request->validateRuleChainForRuntime([
            'nodes' => [
                ['id' => 'start', 'type' => 'start_trigger'],
                ['id' => 'end', 'type' => 'end'],
            ],
            'connections' => [
                ['source' => 'start', 'target' => 'end', 'type' => 'main'],
            ],
        ]);

        $this->assertTrue($result['valid']);
        $this->assertSame('ok', $result['message']);
    }
}
