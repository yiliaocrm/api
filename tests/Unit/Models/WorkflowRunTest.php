<?php

namespace Tests\Unit\Models;

use App\Enums\WorkflowRunStatus;
use App\Models\Workflow;
use App\Models\WorkflowExecution;
use App\Models\WorkflowRun;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class WorkflowRunTest extends TestCase
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
        Schema::dropIfExists('workflow_runs');
        Schema::dropIfExists('workflow_versions');
        Schema::dropIfExists('workflows');

        parent::tearDown();
    }

    protected function createWorkflowTables(): void
    {
        Schema::create('workflows', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('工作流名称');
            $table->text('description')->nullable();
            $table->unsignedBigInteger('category_id')->comment('分类ID');
            $table->unsignedBigInteger('create_user_id')->comment('创建人ID');
            $table->boolean('all_customer')->default(false);
            $table->enum('type', ['trigger', 'periodic'])->default('trigger');
            $table->enum('status', ['active', 'paused'])->default('paused');
            $table->json('cron')->nullable();
            $table->timestamp('last_run_at')->nullable();
            $table->timestamp('next_run_at')->nullable();
            $table->json('rule_chain')->nullable();
            $table->unsignedInteger('dispatch_chunk_size')->default(2000);
            $table->unsignedInteger('dispatch_concurrency')->default(12);
            $table->unsignedInteger('execution_batch_size')->default(200);
            $table->unsignedInteger('max_queue_lag')->default(1000);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('workflow_versions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('workflow_id');
            $table->unsignedInteger('version_no');
            $table->enum('source', ['save', 'publish', 'restore'])->default('save');
            $table->unsignedBigInteger('create_user_id')->nullable();
            $table->json('snapshot');
            $table->timestamps();
        });

        Schema::create('workflow_runs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('workflow_id');
            $table->unsignedBigInteger('workflow_version_id')->nullable();
            $table->string('run_key');
            $table->enum('status', ['pending', 'running', 'completed', 'canceled', 'error'])->default('pending');
            $table->enum('target_mode', ['all', 'groups'])->default('all');
            $table->json('group_ids_json')->nullable();
            $table->unsignedBigInteger('cursor_last_customer_id')->nullable();
            $table->unsignedInteger('total_target')->default(0);
            $table->unsignedInteger('enqueued_count')->default(0);
            $table->unsignedInteger('processed_count')->default(0);
            $table->unsignedInteger('success_count')->default(0);
            $table->unsignedInteger('error_count')->default(0);
            $table->timestamp('cancel_requested_at')->nullable();
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

    public function test_can_create_workflow_run(): void
    {
        $workflow = Workflow::query()->create([
            'name' => 'test-workflow',
            'description' => 'test',
            'category_id' => 1,
            'create_user_id' => 1,
            'all_customer' => true,
            'type' => 'periodic',
            'status' => 'active',
            'rule_chain' => [],
        ]);

        $run = WorkflowRun::query()->create([
            'workflow_id' => $workflow->id,
            'run_key' => '202602261030',
            'status' => 'pending',
            'target_mode' => 'all',
        ]);

        $this->assertDatabaseHas('workflow_runs', [
            'id' => $run->id,
            'workflow_id' => $workflow->id,
            'run_key' => '202602261030',
            'status' => 'pending',
        ]);
    }

    public function test_workflow_run_status_machine(): void
    {
        $workflow = Workflow::query()->create([
            'name' => 'test-workflow',
            'description' => 'test',
            'category_id' => 1,
            'create_user_id' => 1,
            'all_customer' => true,
            'type' => 'periodic',
            'status' => 'active',
            'rule_chain' => [],
        ]);

        $run = WorkflowRun::query()->create([
            'workflow_id' => $workflow->id,
            'run_key' => '202602261030',
            'status' => 'pending',
            'target_mode' => 'all',
        ]);

        // Test start
        $run->start();
        $run->refresh();
        $this->assertEquals(WorkflowRunStatus::RUNNING, $run->status);
        $this->assertNotNull($run->started_at);

        // Test complete
        $run->complete();
        $run->refresh();
        $this->assertEquals(WorkflowRunStatus::COMPLETED, $run->status);
        $this->assertNotNull($run->finished_at);
    }

    public function test_workflow_run_cancel(): void
    {
        $workflow = Workflow::query()->create([
            'name' => 'test-workflow',
            'description' => 'test',
            'category_id' => 1,
            'create_user_id' => 1,
            'all_customer' => true,
            'type' => 'periodic',
            'status' => 'active',
            'rule_chain' => [],
        ]);

        $run = WorkflowRun::query()->create([
            'workflow_id' => $workflow->id,
            'run_key' => '202602261030',
            'status' => 'running',
            'target_mode' => 'all',
        ]);

        // Request cancel
        $run->requestCancel();
        $run->refresh();
        $this->assertNotNull($run->cancel_requested_at);
        $this->assertTrue($run->isCancelRequested());

        // Confirm cancel
        $run->cancel();
        $run->refresh();
        $this->assertEquals(WorkflowRunStatus::CANCELED, $run->status);
    }

    public function test_workflow_run_increment_counts(): void
    {
        $workflow = Workflow::query()->create([
            'name' => 'test-workflow',
            'description' => 'test',
            'category_id' => 1,
            'create_user_id' => 1,
            'all_customer' => true,
            'type' => 'periodic',
            'status' => 'active',
            'rule_chain' => [],
        ]);

        $run = WorkflowRun::query()->create([
            'workflow_id' => $workflow->id,
            'run_key' => '202602261030',
            'status' => 'running',
            'target_mode' => 'all',
        ]);

        $run->incrementEnqueued(100);
        $run->incrementProcessed(50);
        $run->incrementSuccess(45);
        $run->incrementError(5);

        $run->refresh();
        $this->assertEquals(100, $run->enqueued_count);
        $this->assertEquals(50, $run->processed_count);
        $this->assertEquals(45, $run->success_count);
        $this->assertEquals(5, $run->error_count);
    }

    public function test_workflow_run_progress_calculation(): void
    {
        $workflow = Workflow::query()->create([
            'name' => 'test-workflow',
            'description' => 'test',
            'category_id' => 1,
            'create_user_id' => 1,
            'all_customer' => true,
            'type' => 'periodic',
            'status' => 'active',
            'rule_chain' => [],
        ]);

        $run = WorkflowRun::query()->create([
            'workflow_id' => $workflow->id,
            'run_key' => '202602261030',
            'status' => 'running',
            'target_mode' => 'all',
            'total_target' => 1000,
            'processed_count' => 250,
        ]);

        $this->assertEquals(25.0, $run->progress);

        // Edge case: zero total
        $run2 = WorkflowRun::query()->create([
            'workflow_id' => $workflow->id,
            'run_key' => '202602261031',
            'status' => 'running',
            'target_mode' => 'all',
            'total_target' => 0,
        ]);

        $this->assertEquals(0.0, $run2->progress);
    }

    public function test_workflow_run_advance_cursor(): void
    {
        $workflow = Workflow::query()->create([
            'name' => 'test-workflow',
            'description' => 'test',
            'category_id' => 1,
            'create_user_id' => 1,
            'all_customer' => true,
            'type' => 'periodic',
            'status' => 'active',
            'rule_chain' => [],
        ]);

        $run = WorkflowRun::query()->create([
            'workflow_id' => $workflow->id,
            'run_key' => '202602261030',
            'status' => 'running',
            'target_mode' => 'all',
        ]);

        $run->advanceCursor(12345);
        $run->refresh();

        $this->assertEquals(12345, $run->cursor_last_customer_id);
    }

    public function test_workflow_run_fail(): void
    {
        $workflow = Workflow::query()->create([
            'name' => 'test-workflow',
            'description' => 'test',
            'category_id' => 1,
            'create_user_id' => 1,
            'all_customer' => true,
            'type' => 'periodic',
            'status' => 'active',
            'rule_chain' => [],
        ]);

        $run = WorkflowRun::query()->create([
            'workflow_id' => $workflow->id,
            'run_key' => '202602261030',
            'status' => 'running',
            'target_mode' => 'all',
        ]);

        $run->fail('Test error message');
        $run->refresh();

        $this->assertEquals(WorkflowRunStatus::ERROR, $run->status);
        $this->assertEquals('Test error message', $run->error_message);
        $this->assertNotNull($run->finished_at);
    }

    public function test_workflow_execution_scope_by_run(): void
    {
        $workflow = Workflow::query()->create([
            'name' => 'test-workflow',
            'description' => 'test',
            'category_id' => 1,
            'create_user_id' => 1,
            'all_customer' => true,
            'type' => 'periodic',
            'status' => 'active',
            'rule_chain' => [],
        ]);

        $run = WorkflowRun::query()->create([
            'workflow_id' => $workflow->id,
            'run_key' => '202602261030',
            'status' => 'running',
            'target_mode' => 'all',
        ]);

        // Create executions with run_id
        WorkflowExecution::query()->create([
            'workflow_id' => $workflow->id,
            'run_id' => $run->id,
            'status' => 'running',
            'trigger_model_type' => 'customer',
            'trigger_model_id' => '1',
        ]);

        WorkflowExecution::query()->create([
            'workflow_id' => $workflow->id,
            'run_id' => $run->id,
            'status' => 'running',
            'trigger_model_type' => 'customer',
            'trigger_model_id' => '2',
        ]);

        // Create execution without run_id
        WorkflowExecution::query()->create([
            'workflow_id' => $workflow->id,
            'run_id' => null,
            'status' => 'running',
            'trigger_model_type' => 'customer',
            'trigger_model_id' => '3',
        ]);

        $this->assertEquals(2, WorkflowExecution::query()->byRun($run->id)->count());
    }
}
