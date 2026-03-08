<?php

namespace Tests\Unit\Jobs\Workflow;

use App\Jobs\Workflow\RunWorkflowExecutionJob;
use App\Models\Followup;
use App\Models\Workflow;
use App\Models\WorkflowExecution;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class RunWorkflowExecutionJobCreateFollowupNodeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->createWorkflowTables();
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('customer');
        Schema::dropIfExists('customer_log');
        Schema::dropIfExists('followup');
        Schema::dropIfExists('workflow_execution_steps');
        Schema::dropIfExists('workflow_executions');
        Schema::dropIfExists('workflows');

        parent::tearDown();
    }

    public function test_create_followup_node_creates_record_and_completes_execution(): void
    {
        \DB::table('customer')->insert([
            'id' => 'customer-001',
            'name' => 'Alice',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $workflow = Workflow::query()->create([
            'name' => 'create-followup-success',
            'description' => null,
            'category_id' => 1,
            'create_user_id' => 1,
            'all_customer' => true,
            'type' => 'trigger',
            'status' => 'active',
            'rule_chain' => $this->buildRuleChain(),
        ]);

        $execution = WorkflowExecution::query()->create([
            'workflow_id' => $workflow->id,
            'status' => 'running',
            'started_at' => now(),
            'trigger_type' => 'event',
            'trigger_event' => 'customer.created',
            'trigger_model_type' => 'customer',
            'trigger_model_id' => 'customer-001',
            'trigger_user_id' => 77,
            'context_data' => [
                'trigger' => [
                    'event' => 'customer.created',
                    'model_type' => 'customer',
                    'model_id' => 'customer-001',
                    'triggered_at' => now()->toIso8601String(),
                ],
                'payload' => [
                    'id' => 'customer-001',
                    'name' => 'Alice',
                ],
                'runtime' => ['steps' => [], 'node_outputs' => []],
            ],
        ]);

        (new RunWorkflowExecutionJob($execution->id))->handle();

        $execution->refresh();
        $this->assertSame('success', $execution->status->value);

        $followup = Followup::query()->first();
        $this->assertNotNull($followup);
        $this->assertSame('customer-001', $followup->customer_id);
        $this->assertSame('术后回访', $followup->title);
        $this->assertSame(77, $followup->user_id);

        $step = $execution->steps()->where('node_id', 'followup_1')->first();
        $this->assertNotNull($step);
        $this->assertSame('success', $step->status);
        $this->assertTrue((bool) data_get($step->output_data, 'created'));
        $this->assertSame($followup->id, data_get($step->output_data, 'followup_id'));
    }

    public function test_create_followup_node_marks_execution_error_when_customer_id_missing(): void
    {
        $workflow = Workflow::query()->create([
            'name' => 'create-followup-error',
            'description' => null,
            'category_id' => 1,
            'create_user_id' => 1,
            'all_customer' => true,
            'type' => 'trigger',
            'status' => 'active',
            'rule_chain' => $this->buildRuleChain(),
        ]);

        $execution = WorkflowExecution::query()->create([
            'workflow_id' => $workflow->id,
            'status' => 'running',
            'started_at' => now(),
            'context_data' => [
                'trigger' => [
                    'event' => 'customer.created',
                    'model_type' => 'customer',
                    'model_id' => null,
                    'triggered_at' => now()->toIso8601String(),
                ],
                'payload' => [],
                'runtime' => ['steps' => [], 'node_outputs' => []],
            ],
        ]);

        (new RunWorkflowExecutionJob($execution->id))->handle();

        $execution->refresh();
        $this->assertSame('error', $execution->status->value);
        $this->assertStringContainsString('无法获取客户ID', (string) $execution->error_message);
        $this->assertSame(0, Followup::query()->count());
    }

    public function test_create_followup_with_relation_mode_uses_customer_ascription(): void
    {
        // 创建客户记录，归属开发为 user 2001
        \DB::table('customer')->insert([
            'id' => 'customer-rel-001',
            'name' => 'Bob',
            'ascription' => 2001,
            'consultant' => 0,
            'service_id' => 0,
            'doctor_id' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $workflow = Workflow::query()->create([
            'name' => 'followup-relation-ascription',
            'description' => null,
            'category_id' => 1,
            'create_user_id' => 1,
            'all_customer' => true,
            'type' => 'trigger',
            'status' => 'active',
            'rule_chain' => $this->buildRuleChain([
                'followup_user_mode' => 'relation',
                'followup_user_relation' => 'ascription',
                'followup_user' => null,
                'followup_user_fallback' => false,
                'followup_user_fallback_user' => null,
            ]),
        ]);

        $execution = WorkflowExecution::query()->create([
            'workflow_id' => $workflow->id,
            'status' => 'running',
            'started_at' => now(),
            'trigger_type' => 'event',
            'trigger_event' => 'customer.created',
            'trigger_model_type' => 'customer',
            'trigger_model_id' => 'customer-rel-001',
            'trigger_user_id' => 77,
            'context_data' => [
                'trigger' => ['event' => 'customer.created', 'model_type' => 'customer', 'model_id' => 'customer-rel-001', 'triggered_at' => now()->toIso8601String()],
                'payload' => ['id' => 'customer-rel-001', 'name' => 'Bob'],
                'runtime' => ['steps' => [], 'node_outputs' => []],
            ],
        ]);

        (new RunWorkflowExecutionJob($execution->id))->handle();

        $execution->refresh();
        $this->assertSame('success', $execution->status->value);

        $followup = Followup::query()->first();
        $this->assertNotNull($followup);
        $this->assertSame(2001, $followup->followup_user);
    }

    public function test_create_followup_with_relation_mode_fallback_when_relation_empty(): void
    {
        // 客户归属开发为空，兜底员工 3001
        \DB::table('customer')->insert([
            'id' => 'customer-rel-002',
            'name' => 'Carol',
            'ascription' => 0,
            'consultant' => 0,
            'service_id' => 0,
            'doctor_id' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $workflow = Workflow::query()->create([
            'name' => 'followup-relation-fallback',
            'description' => null,
            'category_id' => 1,
            'create_user_id' => 1,
            'all_customer' => true,
            'type' => 'trigger',
            'status' => 'active',
            'rule_chain' => $this->buildRuleChain([
                'followup_user_mode' => 'relation',
                'followup_user_relation' => 'ascription',
                'followup_user' => null,
                'followup_user_fallback' => true,
                'followup_user_fallback_user' => 3001,
            ]),
        ]);

        $execution = WorkflowExecution::query()->create([
            'workflow_id' => $workflow->id,
            'status' => 'running',
            'started_at' => now(),
            'trigger_type' => 'event',
            'trigger_event' => 'customer.created',
            'trigger_model_type' => 'customer',
            'trigger_model_id' => 'customer-rel-002',
            'trigger_user_id' => 77,
            'context_data' => [
                'trigger' => ['event' => 'customer.created', 'model_type' => 'customer', 'model_id' => 'customer-rel-002', 'triggered_at' => now()->toIso8601String()],
                'payload' => ['id' => 'customer-rel-002', 'name' => 'Carol'],
                'runtime' => ['steps' => [], 'node_outputs' => []],
            ],
        ]);

        (new RunWorkflowExecutionJob($execution->id))->handle();

        $execution->refresh();
        $this->assertSame('success', $execution->status->value);

        $followup = Followup::query()->first();
        $this->assertNotNull($followup);
        $this->assertSame(3001, $followup->followup_user);
    }

    public function test_create_followup_with_relation_mode_fails_when_no_relation_and_no_fallback(): void
    {
        // 客户归属为空，无兜底
        \DB::table('customer')->insert([
            'id' => 'customer-rel-003',
            'name' => 'Dave',
            'ascription' => 0,
            'consultant' => 0,
            'service_id' => 0,
            'doctor_id' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $workflow = Workflow::query()->create([
            'name' => 'followup-relation-no-fallback',
            'description' => null,
            'category_id' => 1,
            'create_user_id' => 1,
            'all_customer' => true,
            'type' => 'trigger',
            'status' => 'active',
            'rule_chain' => $this->buildRuleChain([
                'followup_user_mode' => 'relation',
                'followup_user_relation' => 'ascription',
                'followup_user' => null,
                'followup_user_fallback' => false,
                'followup_user_fallback_user' => null,
            ]),
        ]);

        $execution = WorkflowExecution::query()->create([
            'workflow_id' => $workflow->id,
            'status' => 'running',
            'started_at' => now(),
            'trigger_type' => 'event',
            'trigger_event' => 'customer.created',
            'trigger_model_type' => 'customer',
            'trigger_model_id' => 'customer-rel-003',
            'trigger_user_id' => 77,
            'context_data' => [
                'trigger' => ['event' => 'customer.created', 'model_type' => 'customer', 'model_id' => 'customer-rel-003', 'triggered_at' => now()->toIso8601String()],
                'payload' => ['id' => 'customer-rel-003', 'name' => 'Dave'],
                'runtime' => ['steps' => [], 'node_outputs' => []],
            ],
        ]);

        (new RunWorkflowExecutionJob($execution->id))->handle();

        $execution->refresh();
        $this->assertSame('error', $execution->status->value);
        $this->assertStringContainsString('无法确定提醒人员', (string) $execution->error_message);
        $this->assertSame(0, Followup::query()->count());
    }

    /**
     * @return array<string, mixed>
     */
    private function buildRuleChain(array $paramOverrides = []): array
    {
        $defaultParams = [
            'title' => '术后回访',
            'type' => 1,
            'followup_user_mode' => 'specified',
            'followup_user' => 1001,
            'followup_user_relation' => null,
            'followup_user_fallback' => false,
            'followup_user_fallback_user' => null,
            'date_mode' => 'relative',
            'date_offset' => 1,
            'date_unit' => 'days',
        ];

        return [
            'nodes' => [
                ['id' => 'start', 'type' => 'start_trigger', 'name' => '开始'],
                [
                    'id' => 'followup_1',
                    'type' => 'create_followup',
                    'name' => '创建回访',
                    'parameters' => array_merge($defaultParams, $paramOverrides),
                ],
                ['id' => 'end', 'type' => 'end', 'name' => '结束'],
            ],
            'connections' => [
                ['id' => 'c1', 'source' => 'start', 'target' => 'followup_1', 'type' => 'main'],
                ['id' => 'c2', 'source' => 'followup_1', 'target' => 'end', 'type' => 'main'],
            ],
            'layout' => [
                'flow' => [
                    ['nodeId' => 'start', 'order' => 0],
                    ['nodeId' => 'followup_1', 'order' => 1],
                    ['nodeId' => 'end', 'order' => 2],
                ],
            ],
        ];
    }

    private function createWorkflowTables(): void
    {
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

        Schema::create('followup', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('customer_id');
            $table->tinyInteger('type')->nullable();
            $table->tinyInteger('status')->default(1);
            $table->string('title');
            $table->date('date');
            $table->dateTime('time')->nullable();
            $table->text('remark')->nullable();
            $table->integer('followup_user');
            $table->integer('execute_user')->nullable();
            $table->integer('user_id');
            $table->timestamps();
        });

        Schema::create('customer', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('name');
            $table->integer('ascription')->default(0);
            $table->integer('consultant')->default(0);
            $table->integer('service_id')->default(0);
            $table->integer('doctor_id')->default(0);
            $table->timestamps();
        });

        Schema::create('customer_log', function (Blueprint $table) {
            $table->id();
            $table->string('customer_id')->nullable();
            $table->string('action')->nullable();
            $table->integer('user_id')->default(0);
            $table->text('original')->nullable();
            $table->text('dirty')->nullable();
            $table->string('logable_type')->nullable();
            $table->string('logable_id')->nullable();
            $table->timestamps();
        });
    }
}
