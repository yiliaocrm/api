<?php

namespace Tests\Unit\Jobs\Workflow;

use App\Jobs\Workflow\BatchRunWorkflowExecutionJob;
use App\Models\Workflow;
use App\Models\WorkflowExecution;
use App\Models\WorkflowExecutionStep;
use App\Models\WorkflowRun;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class BatchRunWorkflowExecutionJobWaitingResumeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->createTables();
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('workflow_execution_steps');
        Schema::dropIfExists('workflow_executions');
        Schema::dropIfExists('workflow_runs');
        Schema::dropIfExists('workflows');

        parent::tearDown();
    }

    public function test_waiting_execution_is_resumed_and_completed_in_batch_job(): void
    {
        Carbon::setTestNow('2026-03-02 13:00:00');

        $workflow = $this->createWorkflow();
        $run = $this->createRun(dispatchCompletedAt: null, totalTarget: 0);
        $execution = $this->createWaitingExecution($workflow->id, $run->id);

        (new BatchRunWorkflowExecutionJob([$execution->id], $run->id))->handle();

        $execution->refresh();
        $run->refresh();

        $this->assertSame('success', $execution->status->value);
        $this->assertNull($execution->waiting_until);
        $this->assertNull($execution->next_node_id);
        $this->assertSame(1, $run->success_count);
        $this->assertSame(1, $run->processed_count);
        $this->assertSame('running', $run->status->value);

        $this->assertSame(1, WorkflowExecutionStep::query()->where('workflow_execution_id', $execution->id)->count());
        $this->assertSame(
            'end',
            WorkflowExecutionStep::query()->where('workflow_execution_id', $execution->id)->first()->node_type
        );
    }

    public function test_terminal_status_executions_are_skipped_by_batch_query_filter(): void
    {
        Carbon::setTestNow('2026-03-02 13:00:00');

        $workflow = $this->createWorkflow();
        $run = $this->createRun(dispatchCompletedAt: null, totalTarget: 0);

        $successExecution = WorkflowExecution::query()->create([
            'workflow_id' => $workflow->id,
            'run_id' => $run->id,
            'status' => 'success',
            'finished_at' => now(),
            'context_data' => ['runtime' => ['steps' => [], 'node_outputs' => []]],
        ]);
        $errorExecution = WorkflowExecution::query()->create([
            'workflow_id' => $workflow->id,
            'run_id' => $run->id,
            'status' => 'error',
            'finished_at' => now(),
            'context_data' => ['runtime' => ['steps' => [], 'node_outputs' => []]],
        ]);
        $canceledExecution = WorkflowExecution::query()->create([
            'workflow_id' => $workflow->id,
            'run_id' => $run->id,
            'status' => 'canceled',
            'finished_at' => now(),
            'context_data' => ['runtime' => ['steps' => [], 'node_outputs' => []]],
        ]);

        (new BatchRunWorkflowExecutionJob([$successExecution->id, $errorExecution->id, $canceledExecution->id], $run->id))->handle();

        $successExecution->refresh();
        $errorExecution->refresh();
        $canceledExecution->refresh();
        $run->refresh();

        $this->assertSame('success', $successExecution->status->value);
        $this->assertSame('error', $errorExecution->status->value);
        $this->assertSame('canceled', $canceledExecution->status->value);
        $this->assertSame(0, WorkflowExecutionStep::query()->count());
        $this->assertSame(0, $run->processed_count);
        $this->assertSame(0, $run->success_count);
        $this->assertSame(0, $run->error_count);
    }

    public function test_missing_run_returns_safely_without_processing_execution(): void
    {
        Carbon::setTestNow('2026-03-02 13:00:00');

        $workflow = $this->createWorkflow();
        $execution = $this->createWaitingExecution($workflow->id, 99999);

        (new BatchRunWorkflowExecutionJob([$execution->id], 99999))->handle();

        $execution->refresh();

        $this->assertSame('waiting', $execution->status->value);
        $this->assertSame(0, WorkflowExecutionStep::query()->count());
    }

    public function test_dispatch_completed_run_converges_to_completed_after_waiting_execution_processed(): void
    {
        Carbon::setTestNow('2026-03-02 13:00:00');

        $workflow = $this->createWorkflow();
        $run = $this->createRun(dispatchCompletedAt: now()->subMinute(), totalTarget: 1);
        $execution = $this->createWaitingExecution($workflow->id, $run->id);

        (new BatchRunWorkflowExecutionJob([$execution->id], $run->id))->handle();

        $execution->refresh();
        $run->refresh();

        $this->assertSame('success', $execution->status->value);
        $this->assertSame(1, $run->processed_count);
        $this->assertSame(1, $run->success_count);
        $this->assertSame('completed', $run->status->value);
        $this->assertNotNull($run->finished_at);
    }

    private function createWorkflow(): Workflow
    {
        return Workflow::query()->create([
            'name' => 'waiting-resume-workflow',
            'description' => null,
            'category_id' => 1,
            'create_user_id' => 1,
            'all_customer' => true,
            'type' => 'periodic',
            'status' => 'active',
            'rule_chain' => [
                'nodes' => [
                    ['id' => 'wait_1', 'type' => 'wait', 'name' => '等待'],
                    ['id' => 'end_1', 'type' => 'end', 'name' => '结束'],
                ],
                'connections' => [
                    ['id' => 'c1', 'source' => 'wait_1', 'target' => 'end_1', 'type' => 'main'],
                ],
                'layout' => [
                    'flow' => [
                        ['nodeId' => 'wait_1', 'order' => 0],
                        ['nodeId' => 'end_1', 'order' => 1],
                    ],
                ],
            ],
        ]);
    }

    private function createRun(?Carbon $dispatchCompletedAt, int $totalTarget): WorkflowRun
    {
        return WorkflowRun::query()->create([
            'workflow_id' => 1,
            'run_key' => 'batch-run-'.uniqid(),
            'status' => 'running',
            'dispatch_completed_at' => $dispatchCompletedAt,
            'total_target' => $totalTarget,
            'processed_count' => 0,
            'success_count' => 0,
            'error_count' => 0,
            'enqueued_count' => $totalTarget,
        ]);
    }

    private function createWaitingExecution(int $workflowId, int $runId): WorkflowExecution
    {
        return WorkflowExecution::query()->create([
            'workflow_id' => $workflowId,
            'run_id' => $runId,
            'status' => 'waiting',
            'current_node_id' => 'wait_1',
            'next_node_id' => 'end_1',
            'waiting_until' => now()->subMinute(),
            'context_data' => [
                'trigger' => ['event' => 'periodic.scheduled', 'type' => 'periodic'],
                'payload' => [],
                'runtime' => [
                    'steps' => [],
                    'node_outputs' => [
                        'wait_1' => ['waiting_until' => now()->subMinute()->toIso8601String()],
                    ],
                ],
            ],
        ]);
    }

    private function createTables(): void
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

        Schema::create('workflow_runs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('workflow_id');
            $table->unsignedBigInteger('workflow_version_id')->nullable();
            $table->string('run_key');
            $table->enum('status', ['pending', 'running', 'completed', 'canceled', 'error'])->default('pending');
            $table->string('target_mode')->default('all');
            $table->json('group_ids_json')->nullable();
            $table->string('cursor_last_customer_id')->nullable();
            $table->unsignedInteger('total_target')->default(0);
            $table->unsignedInteger('enqueued_count')->default(0);
            $table->unsignedInteger('processed_count')->default(0);
            $table->unsignedInteger('success_count')->default(0);
            $table->unsignedInteger('error_count')->default(0);
            $table->timestamp('cancel_requested_at')->nullable();
            $table->timestamp('dispatch_completed_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();
        });

        Schema::create('workflow_executions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('workflow_id');
            $table->unsignedBigInteger('workflow_version_id')->nullable();
            $table->unsignedBigInteger('run_id')->nullable();
            $table->enum('status', ['running', 'success', 'error', 'waiting', 'canceled'])->default('running');
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
            $table->enum('status', ['running', 'success', 'error', 'skipped'])->default('running');
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
