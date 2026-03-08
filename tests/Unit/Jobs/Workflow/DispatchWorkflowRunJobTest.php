<?php

namespace Tests\Unit\Jobs\Workflow;

use App\Jobs\Workflow\DispatchWorkflowRunJob;
use App\Models\Customer;
use App\Models\Workflow;
use App\Models\WorkflowRun;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DispatchWorkflowRunJobTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->createWorkflowTables();
        $this->createCustomerTable();
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('workflow_customer_groups');
        Schema::dropIfExists('customer_group_details');
        Schema::dropIfExists('customer_groups');
        Schema::dropIfExists('customer');
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
            $table->unique(['workflow_id', 'run_id', 'trigger_model_type', 'trigger_model_id'], 'wf_exec_upsert_uniq');
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

    protected function createCustomerTable(): void
    {
        Schema::create('customer', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name')->nullable();
            $table->string('phone')->nullable();
            $table->timestamps();
        });

        Schema::create('customer_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('customer_group_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_group_id');
            $table->uuid('customer_id');
            $table->timestamps();
        });

        Schema::create('workflow_customer_groups', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('workflow_id');
            $table->unsignedBigInteger('customer_group_id');
            $table->timestamps();
        });
    }

    public function test_dispatch_creates_executions_for_all_customers(): void
    {
        // Create customers
        for ($i = 1; $i <= 10; $i++) {
            Customer::withoutEvents(fn () => Customer::query()->create([
                'name' => "Customer {$i}",
                'phone' => "138000000{$i}",
            ]));
        }

        // Create workflow with all_customer = true
        $workflow = Workflow::query()->create([
            'name' => 'test-periodic-workflow',
            'description' => 'test',
            'category_id' => 1,
            'create_user_id' => 1,
            'all_customer' => true,
            'type' => 'periodic',
            'status' => 'active',
            'rule_chain' => [
                'nodes' => [
                    [
                        'id' => 'start-1',
                        'type' => 'start_periodic',
                        'name' => '开始',
                    ],
                ],
                'connections' => [],
                'layout' => ['flow' => []],
            ],
        ]);

        // Create workflow version
        $workflow->versions()->create([
            'version_no' => 1,
            'source' => 'publish',
            'create_user_id' => 1,
            'snapshot' => $workflow->rule_chain,
        ]);

        // Create workflow run
        $run = WorkflowRun::query()->create([
            'workflow_id' => $workflow->id,
            'run_key' => '202602261030',
            'status' => 'pending',
            'target_mode' => 'all',
        ]);

        // Run dispatch job
        $job = new DispatchWorkflowRunJob($run->id);
        $job->handle();

        // Verify executions were created
        $run->refresh();
        $this->assertEquals(10, $run->total_target);
        $this->assertEquals(10, $run->enqueued_count);
    }

    public function test_dispatch_respects_customer_groups(): void
    {
        // Create customers
        $customer1 = Customer::withoutEvents(fn () => Customer::query()->create([
            'name' => 'Customer 1',
            'phone' => '13800000001',
        ]));
        $customer2 = Customer::withoutEvents(fn () => Customer::query()->create([
            'name' => 'Customer 2',
            'phone' => '13800000002',
        ]));
        $customer3 = Customer::withoutEvents(fn () => Customer::query()->create([
            'name' => 'Customer 3',
            'phone' => '13800000003',
        ]));

        // Create customer group
        $group = \App\Models\CustomerGroup::query()->create([
            'name' => 'Test Group',
        ]);

        // Add only customer1 and customer2 to the group
        \DB::table('customer_group_details')->insert([
            ['customer_group_id' => $group->id, 'customer_id' => $customer1->id],
            ['customer_group_id' => $group->id, 'customer_id' => $customer2->id],
        ]);

        // Create workflow with specific customer groups
        $workflow = Workflow::query()->create([
            'name' => 'test-periodic-workflow',
            'description' => 'test',
            'category_id' => 1,
            'create_user_id' => 1,
            'all_customer' => false,
            'type' => 'periodic',
            'status' => 'active',
            'rule_chain' => [
                'nodes' => [
                    [
                        'id' => 'start-1',
                        'type' => 'start_periodic',
                        'name' => '开始',
                    ],
                ],
                'connections' => [],
                'layout' => ['flow' => []],
            ],
        ]);

        // Attach customer group
        $workflow->customerGroups()->attach($group->id);

        // Create workflow version
        $workflow->versions()->create([
            'version_no' => 1,
            'source' => 'publish',
            'create_user_id' => 1,
            'snapshot' => $workflow->rule_chain,
        ]);

        // Create workflow run
        $run = WorkflowRun::query()->create([
            'workflow_id' => $workflow->id,
            'run_key' => '202602261030',
            'status' => 'pending',
            'target_mode' => 'groups',
            'group_ids_json' => [$group->id],
        ]);

        // Run dispatch job
        $job = new DispatchWorkflowRunJob($run->id);
        $job->handle();

        // Verify only 2 executions were created (customer1 and customer2)
        $run->refresh();
        $this->assertEquals(2, $run->total_target);
        $this->assertEquals(2, $run->enqueued_count);
    }

    public function test_dispatch_idempotent(): void
    {
        // Create customers
        for ($i = 1; $i <= 5; $i++) {
            Customer::withoutEvents(fn () => Customer::query()->create([
                'name' => "Customer {$i}",
                'phone' => "138000000{$i}",
            ]));
        }

        // Create workflow
        $workflow = Workflow::query()->create([
            'name' => 'test-periodic-workflow',
            'description' => 'test',
            'category_id' => 1,
            'create_user_id' => 1,
            'all_customer' => true,
            'type' => 'periodic',
            'status' => 'active',
            'rule_chain' => [
                'nodes' => [
                    [
                        'id' => 'start-1',
                        'type' => 'start_periodic',
                        'name' => '开始',
                    ],
                ],
                'connections' => [],
                'layout' => ['flow' => []],
            ],
        ]);

        // Create workflow version
        $workflow->versions()->create([
            'version_no' => 1,
            'source' => 'publish',
            'create_user_id' => 1,
            'snapshot' => $workflow->rule_chain,
        ]);

        // Create workflow run
        $run = WorkflowRun::query()->create([
            'workflow_id' => $workflow->id,
            'run_key' => '202602261030',
            'status' => 'pending',
            'target_mode' => 'all',
        ]);

        // Run dispatch job twice
        $job1 = new DispatchWorkflowRunJob($run->id);
        $job1->handle();

        $job2 = new DispatchWorkflowRunJob($run->id);
        $job2->handle();

        // Should still only have 5 executions
        $run->refresh();
        $this->assertEquals(5, $run->total_target);
    }

    public function test_dispatch_handles_cancel_request(): void
    {
        // Create customers
        for ($i = 1; $i <= 20; $i++) {
            Customer::withoutEvents(fn () => Customer::query()->create([
                'name' => "Customer {$i}",
                'phone' => "138000000{$i}",
            ]));
        }

        // Create workflow
        $workflow = Workflow::query()->create([
            'name' => 'test-periodic-workflow',
            'description' => 'test',
            'category_id' => 1,
            'create_user_id' => 1,
            'all_customer' => true,
            'type' => 'periodic',
            'status' => 'active',
            'rule_chain' => [
                'nodes' => [
                    [
                        'id' => 'start-1',
                        'type' => 'start_periodic',
                        'name' => '开始',
                    ],
                ],
                'connections' => [],
                'layout' => ['flow' => []],
            ],
        ]);

        // Create workflow version
        $workflow->versions()->create([
            'version_no' => 1,
            'source' => 'publish',
            'create_user_id' => 1,
            'snapshot' => $workflow->rule_chain,
        ]);

        // Create workflow run with cancel requested
        $run = WorkflowRun::query()->create([
            'workflow_id' => $workflow->id,
            'run_key' => '202602261030',
            'status' => 'running',
            'target_mode' => 'all',
            'cancel_requested_at' => now(),
        ]);

        // Run dispatch job - should exit early
        $job = new DispatchWorkflowRunJob($run->id);
        $job->handle();

        // Run should be canceled
        $run->refresh();
        $this->assertEquals(\App\Enums\WorkflowRunStatus::CANCELED, $run->status);
    }
}
