<?php

namespace Tests\Feature\Workflow;

use App\Events\Web\WorkflowTriggerEvent;
use App\Jobs\Workflow\RunWorkflowExecutionJob;
use App\Listeners\Workflow\DispatchWorkflowExecutionFromTrigger;
use App\Models\Workflow;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DispatchWorkflowExecutionFromTriggerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->createWorkflowTables();
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('workflow_executions');
        Schema::dropIfExists('workflow_versions');
        Schema::dropIfExists('customer_group_details');
        Schema::dropIfExists('workflow_customer_groups');
        Schema::dropIfExists('customer_groups');
        Schema::dropIfExists('workflows');

        parent::tearDown();
    }

    public function test_all_customer_workflow_dispatches_when_event_matches(): void
    {
        Queue::fake();
        $workflow = $this->createWorkflow(true, [], ['customer.created']);

        (new DispatchWorkflowExecutionFromTrigger)->handle(
            new WorkflowTriggerEvent('customer.created', 'customer', 'c-1', [])
        );

        $this->assertSame(1, DB::table('workflow_executions')->count());
        $this->assertSame($workflow->id, (int) DB::table('workflow_executions')->value('workflow_id'));
        Queue::assertPushed(RunWorkflowExecutionJob::class, 1);
    }

    public function test_non_all_customer_workflow_dispatches_only_when_customer_in_target_groups(): void
    {
        Queue::fake();
        $workflow = $this->createWorkflow(false, [191], ['customer.created']);
        DB::table('customer_group_details')->insert([
            'customer_id' => 'c-1',
            'customer_group_id' => 191,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        (new DispatchWorkflowExecutionFromTrigger)->handle(
            new WorkflowTriggerEvent('customer.created', 'customer', 'c-1', [])
        );

        $this->assertSame(1, DB::table('workflow_executions')->count());
        $this->assertSame($workflow->id, (int) DB::table('workflow_executions')->value('workflow_id'));
        Queue::assertPushed(RunWorkflowExecutionJob::class, 1);
    }

    public function test_non_all_customer_workflow_is_not_dispatched_when_customer_not_in_target_groups(): void
    {
        Queue::fake();
        $this->createWorkflow(false, [191], ['customer.created']);
        DB::table('customer_group_details')->insert([
            'customer_id' => 'c-2',
            'customer_group_id' => 191,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        (new DispatchWorkflowExecutionFromTrigger)->handle(
            new WorkflowTriggerEvent('customer.created', 'customer', 'c-1', [])
        );

        $this->assertSame(0, DB::table('workflow_executions')->count());
        Queue::assertNothingPushed();
    }

    public function test_non_all_customer_workflow_is_not_dispatched_when_no_groups_bound(): void
    {
        Queue::fake();
        $this->createWorkflow(false, [], ['customer.created']);

        (new DispatchWorkflowExecutionFromTrigger)->handle(
            new WorkflowTriggerEvent('customer.created', 'customer', 'c-1', [])
        );

        $this->assertSame(0, DB::table('workflow_executions')->count());
        Queue::assertNothingPushed();
    }

    public function test_non_customer_trigger_can_match_customer_from_payload(): void
    {
        Queue::fake();
        $workflow = $this->createWorkflow(false, [191], ['reservation.created']);
        DB::table('customer_group_details')->insert([
            'customer_id' => 'c-9',
            'customer_group_id' => 191,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        (new DispatchWorkflowExecutionFromTrigger)->handle(
            new WorkflowTriggerEvent('reservation.created', 'reservation', 'r-1', ['customer_id' => 'c-9'])
        );

        $this->assertSame(1, DB::table('workflow_executions')->count());
        $this->assertSame($workflow->id, (int) DB::table('workflow_executions')->value('workflow_id'));
        Queue::assertPushed(RunWorkflowExecutionJob::class, 1);
    }

    public function test_workflow_is_not_dispatched_when_event_not_matched(): void
    {
        Queue::fake();
        $this->createWorkflow(true, [], ['customer.updated']);

        (new DispatchWorkflowExecutionFromTrigger)->handle(
            new WorkflowTriggerEvent('customer.created', 'customer', 'c-1', [])
        );

        $this->assertSame(0, DB::table('workflow_executions')->count());
        Queue::assertNothingPushed();
    }

    public function test_trigger_workflow_updates_last_run_at(): void
    {
        Queue::fake();
        $workflow = $this->createWorkflow(true, [], ['customer.created']);
        $this->assertNull($workflow->last_run_at);

        $before = now()->startOfSecond();

        (new DispatchWorkflowExecutionFromTrigger)->handle(
            new WorkflowTriggerEvent('customer.created', 'customer', 'c-1', [])
        );

        $workflow->refresh();
        $this->assertNotNull($workflow->last_run_at);
        $this->assertTrue(
            $workflow->last_run_at->gte($before),
            'last_run_at should be updated to current time after trigger execution'
        );
    }

    public function test_trigger_workflow_does_not_update_last_run_at_when_event_not_matched(): void
    {
        Queue::fake();
        $workflow = $this->createWorkflow(true, [], ['customer.updated']);
        $this->assertNull($workflow->last_run_at);

        (new DispatchWorkflowExecutionFromTrigger)->handle(
            new WorkflowTriggerEvent('customer.created', 'customer', 'c-1', [])
        );

        $workflow->refresh();
        $this->assertNull($workflow->last_run_at, 'last_run_at should remain null when event does not match');
    }

    /**
     * @param  array<int, int>  $customerGroupIds
     * @param  array<int, string>  $triggerEvents
     */
    private function createWorkflow(bool $allCustomer, array $customerGroupIds, array $triggerEvents): Workflow
    {
        foreach ($customerGroupIds as $groupId) {
            DB::table('customer_groups')->insert([
                'id' => $groupId,
                'name' => "group-{$groupId}",
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $workflow = Workflow::query()->create([
            'name' => 'trigger-workflow',
            'description' => null,
            'category_id' => 1,
            'create_user_id' => 1,
            'active' => true,
            'all_customer' => $allCustomer,
            'type' => 'trigger',
            'status' => 'active',
            'cron' => null,
            'version' => '1.0.0',
            'rule_chain' => [
                'nodes' => [
                    [
                        'id' => 'start-1',
                        'type' => 'start_trigger',
                        'parameters' => [
                            'triggerEvents' => $triggerEvents,
                        ],
                    ],
                    [
                        'id' => 'end-1',
                        'type' => 'end',
                    ],
                ],
                'connections' => [
                    [
                        'source' => 'start-1',
                        'target' => 'end-1',
                        'type' => 'main',
                    ],
                ],
            ],
        ]);

        foreach ($customerGroupIds as $groupId) {
            DB::table('workflow_customer_groups')->insert([
                'workflow_id' => $workflow->id,
                'customer_group_id' => $groupId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return $workflow;
    }

    private function createWorkflowTables(): void
    {
        Schema::create('workflows', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedBigInteger('category_id');
            $table->unsignedBigInteger('create_user_id');
            $table->string('workflow_id')->nullable();
            $table->boolean('active')->default(false);
            $table->boolean('all_customer')->default(false);
            $table->string('type')->default('trigger');
            $table->string('status')->default('paused');
            $table->string('cron')->nullable();
            $table->timestamp('last_run_at')->nullable();
            $table->timestamp('next_run_at')->nullable();
            $table->string('version')->default('1.0.0');
            $table->json('rule_chain')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('workflow_versions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('workflow_id');
            $table->unsignedInteger('version_no');
            $table->string('source')->default('save');
            $table->unsignedBigInteger('create_user_id')->nullable();
            $table->json('snapshot');
            $table->timestamps();
            $table->unique(['workflow_id', 'version_no']);
            $table->index('workflow_id');
        });

        Schema::create('customer_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->timestamps();
        });

        Schema::create('workflow_customer_groups', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('workflow_id');
            $table->unsignedBigInteger('customer_group_id');
            $table->timestamps();
        });

        Schema::create('customer_group_details', function (Blueprint $table) {
            $table->string('customer_id');
            $table->unsignedBigInteger('customer_group_id');
            $table->timestamps();
            $table->index(['customer_id', 'customer_group_id']);
        });

        Schema::create('workflow_executions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('workflow_id');
            $table->unsignedBigInteger('workflow_version_id')->nullable();
            $table->string('workflow_execution_id')->nullable();
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
    }
}
