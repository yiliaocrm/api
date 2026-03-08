<?php

namespace Tests\Unit\Jobs\Workflow;

use App\Jobs\Workflow\RunWorkflowExecutionJob;
use App\Models\Workflow;
use App\Models\WorkflowExecution;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class RunWorkflowExecutionJobPeriodicTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->createWorkflowTables();
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('workflow_execution_steps');
        Schema::dropIfExists('workflow_executions');
        Schema::dropIfExists('workflows');
        Schema::dropIfExists('customer');

        parent::tearDown();
    }

    public function test_start_periodic_node_is_recognized_and_executed(): void
    {
        $workflow = Workflow::query()->create([
            'name' => 'periodic-test',
            'description' => null,
            'category_id' => 1,
            'create_user_id' => 1,
            'all_customer' => true,
            'type' => 'periodic',
            'status' => 'active',
            'rule_chain' => $this->buildPeriodicRuleChain(),
        ]);

        $execution = WorkflowExecution::query()->create([
            'workflow_id' => $workflow->id,
            'status' => 'running',
            'started_at' => now(),
            'trigger_type' => 'periodic',
            'trigger_event' => 'periodic.scheduled',
            'trigger_model_type' => 'customer',
            'trigger_model_id' => 'non-existent-uuid',
            'context_data' => [
                'trigger' => [
                    'event' => 'periodic.scheduled',
                    'type' => 'periodic',
                    'customer_id' => 'non-existent-uuid',
                    'tenant_id' => null,
                    'triggered_at' => now()->toIso8601String(),
                ],
                'payload' => [],
                'runtime' => ['steps' => [], 'node_outputs' => []],
            ],
        ]);

        (new RunWorkflowExecutionJob($execution->id))->handle();

        $execution->refresh();

        $this->assertSame('success', $execution->status->value);
        $this->assertNull($execution->error_message);

        // 验证 start_periodic 步骤已执行
        $steps = $execution->steps()->orderBy('id')->get();
        $startStep = $steps->firstWhere('node_type', 'start_periodic');
        $this->assertNotNull($startStep, 'start_periodic 步骤应当存在');
        $this->assertSame('success', $startStep->status);

        // output 应该包含 started=true
        $this->assertTrue(data_get($startStep->output_data, 'started'));
    }

    public function test_start_periodic_node_sets_empty_payload_when_customer_not_found(): void
    {
        $workflow = Workflow::query()->create([
            'name' => 'periodic-missing-customer',
            'description' => null,
            'category_id' => 1,
            'create_user_id' => 1,
            'all_customer' => true,
            'type' => 'periodic',
            'status' => 'active',
            'rule_chain' => $this->buildPeriodicRuleChain(),
        ]);

        $execution = WorkflowExecution::query()->create([
            'workflow_id' => $workflow->id,
            'status' => 'running',
            'started_at' => now(),
            'context_data' => [
                'trigger' => [
                    'event' => 'periodic.scheduled',
                    'type' => 'periodic',
                    'customer_id' => 'missing-uuid-12345',
                    'triggered_at' => now()->toIso8601String(),
                ],
                'payload' => [],
                'runtime' => ['steps' => []],
            ],
        ]);

        (new RunWorkflowExecutionJob($execution->id))->handle();

        $execution->refresh();
        $this->assertSame('success', $execution->status->value);

        // payload 应为空数组（客户不存在时不报错，只是 payload 为空）
        $payload = data_get($execution->context_data, 'payload');
        $this->assertIsArray($payload);
        $this->assertEmpty($payload);
    }

    public function test_periodic_workflow_completes_full_chain(): void
    {
        $workflow = Workflow::query()->create([
            'name' => 'periodic-full-chain',
            'description' => null,
            'category_id' => 1,
            'create_user_id' => 1,
            'all_customer' => true,
            'type' => 'periodic',
            'status' => 'active',
            'rule_chain' => [
                'nodes' => [
                    [
                        'id' => 'start',
                        'type' => 'start_periodic',
                        'name' => '周期开始',
                        'parameters' => [
                            'journeyType' => 'periodic',
                            'runTime' => 'day',
                            'dayInterval' => 1,
                            'executeTime' => '09:00',
                        ],
                    ],
                    [
                        'id' => 'log_1',
                        'type' => 'log',
                        'name' => '日志',
                        'parameters' => ['message' => '周期执行测试'],
                    ],
                    ['id' => 'end', 'type' => 'end', 'name' => '结束'],
                ],
                'connections' => [
                    ['id' => 'c1', 'source' => 'start', 'target' => 'log_1', 'type' => 'main'],
                    ['id' => 'c2', 'source' => 'log_1', 'target' => 'end', 'type' => 'main'],
                ],
                'layout' => [
                    'flow' => [
                        ['nodeId' => 'start', 'order' => 0],
                        ['nodeId' => 'log_1', 'order' => 1],
                        ['nodeId' => 'end', 'order' => 2],
                    ],
                ],
            ],
        ]);

        $execution = WorkflowExecution::query()->create([
            'workflow_id' => $workflow->id,
            'status' => 'running',
            'started_at' => now(),
            'context_data' => [
                'trigger' => [
                    'event' => 'periodic.scheduled',
                    'type' => 'periodic',
                    'customer_id' => null,
                    'triggered_at' => now()->toIso8601String(),
                ],
                'payload' => [],
                'runtime' => ['steps' => []],
            ],
        ]);

        (new RunWorkflowExecutionJob($execution->id))->handle();

        $execution->refresh();
        $this->assertSame('success', $execution->status->value);

        $steps = $execution->steps()->orderBy('id')->get();
        $nodeTypes = $steps->pluck('node_type')->all();

        $this->assertContains('start_periodic', $nodeTypes);
        $this->assertContains('log', $nodeTypes);
        $this->assertContains('end', $nodeTypes);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildPeriodicRuleChain(): array
    {
        return [
            'nodes' => [
                [
                    'id' => 'start',
                    'type' => 'start_periodic',
                    'name' => '周期开始',
                    'parameters' => [
                        'journeyType' => 'periodic',
                        'targetPopulation' => ['all'],
                        'runTime' => 'day',
                        'dayInterval' => 1,
                        'executeTime' => '09:00',
                    ],
                ],
                ['id' => 'end', 'type' => 'end', 'name' => '结束'],
            ],
            'connections' => [
                ['id' => 'c1', 'source' => 'start', 'target' => 'end', 'type' => 'main'],
            ],
            'layout' => [
                'flow' => [
                    ['nodeId' => 'start', 'order' => 0],
                    ['nodeId' => 'end', 'order' => 1],
                ],
            ],
        ];
    }

    private function createWorkflowTables(): void
    {
        Schema::create('customer', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name')->nullable();
            $table->string('phone')->nullable();
            $table->timestamps();
        });

        Schema::create('workflows', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedBigInteger('category_id');
            $table->unsignedBigInteger('create_user_id');
            $table->boolean('all_customer')->default(false);
            $table->string('type')->default('trigger');
            $table->string('status')->default('paused');
            $table->string('cron')->nullable();
            $table->timestamp('last_run_at')->nullable();
            $table->timestamp('next_run_at')->nullable();
            $table->json('rule_chain')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('workflow_executions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('workflow_id');
            $table->unsignedBigInteger('workflow_version_id')->nullable();
            $table->string('status')->default('running');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->integer('duration')->nullable();
            $table->json('input_data')->nullable();
            $table->json('output_data')->nullable();
            $table->text('error_message')->nullable();
            $table->json('execution_data')->nullable();
            $table->string('current_node_id')->nullable();
            $table->string('next_node_id')->nullable();
            $table->json('context_data')->nullable();
            $table->timestamp('waiting_until')->nullable();
            $table->string('trigger_event')->nullable();
            $table->string('trigger_model_type')->nullable();
            $table->string('trigger_model_id')->nullable();
            $table->unsignedInteger('lock_version')->default(0);
            $table->string('trigger_type')->nullable();
            $table->unsignedBigInteger('trigger_user_id')->nullable();
            $table->timestamps();
        });

        Schema::create('workflow_execution_steps', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('workflow_execution_id');
            $table->unsignedBigInteger('workflow_version_id')->nullable();
            $table->string('node_id')->nullable();
            $table->string('node_type')->nullable();
            $table->string('node_name')->nullable();
            $table->string('status')->default('running');
            $table->unsignedInteger('attempt')->default(1);
            $table->json('input_data')->nullable();
            $table->json('output_data')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->timestamps();
        });
    }
}
