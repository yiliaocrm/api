<?php

namespace Tests\Unit\Console\Commands\Tenant;

use App\Console\Commands\Tenant\WorkflowDispatchWaitingCommand;
use App\Jobs\Workflow\BatchRunWorkflowExecutionJob;
use App\Jobs\Workflow\RunWorkflowExecutionJob;
use App\Models\WorkflowExecution;
use App\Models\WorkflowRun;
use Carbon\Carbon;
use Illuminate\Console\OutputStyle;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Tests\TestCase;

class WorkflowDispatchWaitingCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->createTables();
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('workflow_executions');
        Schema::dropIfExists('workflow_runs');

        parent::tearDown();
    }

    public function test_recover_all_dispatches_single_job_for_trigger_executions_without_run_id(): void
    {
        Queue::fake();
        Carbon::setTestNow('2026-03-02 12:00:00');

        $dueExecution = WorkflowExecution::query()->create([
            'workflow_id' => 1,
            'run_id' => null,
            'status' => 'waiting',
            'waiting_until' => now()->subMinute(),
        ]);

        WorkflowExecution::query()->create([
            'workflow_id' => 1,
            'run_id' => null,
            'status' => 'waiting',
            'waiting_until' => now()->addMinute(),
        ]);

        $this->invokeRecoverAll(limit: 200);

        Queue::assertPushed(RunWorkflowExecutionJob::class, function (RunWorkflowExecutionJob $job) use ($dueExecution) {
            return $job->executionId === $dueExecution->id;
        });
        Queue::assertNotPushed(BatchRunWorkflowExecutionJob::class);
    }

    public function test_recover_all_groups_periodic_executions_by_run_and_skips_canceled_run(): void
    {
        Queue::fake();
        Carbon::setTestNow('2026-03-02 12:00:00');

        $run1 = WorkflowRun::query()->create([
            'workflow_id' => 100,
            'run_key' => 'r1',
            'status' => 'running',
        ]);
        $run2 = WorkflowRun::query()->create([
            'workflow_id' => 100,
            'run_key' => 'r2',
            'status' => 'running',
        ]);
        $canceledRun = WorkflowRun::query()->create([
            'workflow_id' => 100,
            'run_key' => 'r3',
            'status' => 'canceled',
        ]);

        $r1e1 = WorkflowExecution::query()->create([
            'workflow_id' => 100,
            'run_id' => $run1->id,
            'status' => 'waiting',
            'waiting_until' => now()->subMinute(),
        ]);
        $r1e2 = WorkflowExecution::query()->create([
            'workflow_id' => 100,
            'run_id' => $run1->id,
            'status' => 'waiting',
            'waiting_until' => now()->subMinute(),
        ]);
        $r2e1 = WorkflowExecution::query()->create([
            'workflow_id' => 100,
            'run_id' => $run2->id,
            'status' => 'waiting',
            'waiting_until' => now()->subMinute(),
        ]);
        WorkflowExecution::query()->create([
            'workflow_id' => 100,
            'run_id' => $canceledRun->id,
            'status' => 'waiting',
            'waiting_until' => now()->subMinute(),
        ]);

        $this->invokeRecoverAll(limit: 200);

        Queue::assertNotPushed(RunWorkflowExecutionJob::class);
        Queue::assertPushed(BatchRunWorkflowExecutionJob::class, 2);

        Queue::assertPushed(BatchRunWorkflowExecutionJob::class, function (BatchRunWorkflowExecutionJob $job) use ($run1, $r1e1, $r1e2) {
            $ids = $job->executionIds;
            sort($ids);
            $expected = [$r1e1->id, $r1e2->id];
            sort($expected);

            return $job->runId === $run1->id && $ids === $expected;
        });

        Queue::assertPushed(BatchRunWorkflowExecutionJob::class, function (BatchRunWorkflowExecutionJob $job) use ($run2, $r2e1) {
            return $job->runId === $run2->id && $job->executionIds === [$r2e1->id];
        });
    }

    public function test_recover_by_run_only_dispatches_target_run_executions(): void
    {
        Queue::fake();
        Carbon::setTestNow('2026-03-02 12:00:00');

        $targetRun = WorkflowRun::query()->create([
            'workflow_id' => 200,
            'run_key' => 'target-run',
            'status' => 'running',
        ]);
        $otherRun = WorkflowRun::query()->create([
            'workflow_id' => 200,
            'run_key' => 'other-run',
            'status' => 'running',
        ]);

        $targetExecution1 = WorkflowExecution::query()->create([
            'workflow_id' => 200,
            'run_id' => $targetRun->id,
            'status' => 'waiting',
            'waiting_until' => now()->subMinute(),
        ]);
        WorkflowExecution::query()->create([
            'workflow_id' => 200,
            'run_id' => $targetRun->id,
            'status' => 'waiting',
            'waiting_until' => now()->subMinute(),
        ]);
        WorkflowExecution::query()->create([
            'workflow_id' => 200,
            'run_id' => $otherRun->id,
            'status' => 'waiting',
            'waiting_until' => now()->subMinute(),
        ]);

        // limit=1 模拟 --run-id 指定恢复时的条数限制
        $this->invokeRecoverByRun($targetRun->id, 1);

        Queue::assertNotPushed(RunWorkflowExecutionJob::class);
        Queue::assertPushed(BatchRunWorkflowExecutionJob::class, 1);
        Queue::assertPushed(BatchRunWorkflowExecutionJob::class, function (BatchRunWorkflowExecutionJob $job) use ($targetRun, $targetExecution1) {
            return $job->runId === $targetRun->id && $job->executionIds === [$targetExecution1->id];
        });
    }

    public function test_recover_all_skips_not_due_waiting_executions(): void
    {
        Queue::fake();
        Carbon::setTestNow('2026-03-02 12:00:00');

        $run = WorkflowRun::query()->create([
            'workflow_id' => 300,
            'run_key' => 'future-run',
            'status' => 'running',
        ]);

        WorkflowExecution::query()->create([
            'workflow_id' => 300,
            'run_id' => null,
            'status' => 'waiting',
            'waiting_until' => now()->addMinute(),
        ]);
        WorkflowExecution::query()->create([
            'workflow_id' => 300,
            'run_id' => $run->id,
            'status' => 'waiting',
            'waiting_until' => now()->addMinute(),
        ]);

        $this->invokeRecoverAll(limit: 200);

        Queue::assertNothingPushed();
    }

    private function invokeRecoverAll(int $limit): void
    {
        $command = new WorkflowDispatchWaitingCommand;
        $command->setLaravel($this->app);
        $command->setOutput(new OutputStyle(new ArrayInput([]), new NullOutput));

        $method = new \ReflectionMethod($command, 'recoverAll');
        $method->setAccessible(true);
        $method->invoke($command, $this->fakeTenant(), $limit);
    }

    private function invokeRecoverByRun(int $runId, int $limit): void
    {
        $command = new WorkflowDispatchWaitingCommand;
        $command->setLaravel($this->app);
        $command->setOutput(new OutputStyle(new ArrayInput([]), new NullOutput));

        $method = new \ReflectionMethod($command, 'recoverByRun');
        $method->setAccessible(true);
        $method->invoke($command, $this->fakeTenant(), $runId, $limit);
    }

    private function fakeTenant(): object
    {
        return new class
        {
            public string $id = 'test-tenant';
        };
    }

    private function createTables(): void
    {
        Schema::create('workflow_runs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('workflow_id');
            $table->unsignedBigInteger('workflow_version_id')->nullable();
            $table->string('run_key');
            $table->enum('status', ['pending', 'running', 'completed', 'canceled', 'error'])->default('pending');
            $table->timestamp('dispatch_completed_at')->nullable();
            $table->timestamp('cancel_requested_at')->nullable();
            $table->unsignedInteger('total_target')->default(0);
            $table->unsignedInteger('enqueued_count')->default(0);
            $table->unsignedInteger('processed_count')->default(0);
            $table->unsignedInteger('success_count')->default(0);
            $table->unsignedInteger('error_count')->default(0);
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
    }
}
