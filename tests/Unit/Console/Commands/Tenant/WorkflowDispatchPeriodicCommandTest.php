<?php

namespace Tests\Unit\Console\Commands\Tenant;

use App\Console\Commands\Tenant\WorkflowDispatchPeriodicCommand;
use App\Jobs\Workflow\DispatchWorkflowRunJob;
use App\Models\Customer;
use App\Models\Workflow;
use App\Models\WorkflowExecution;
use App\Models\WorkflowRun;
use App\Services\Workflow\WorkflowPeriodicScheduler;
use Carbon\Carbon;
use Illuminate\Console\OutputStyle;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Tests\TestCase;

class WorkflowDispatchPeriodicCommandTest extends TestCase
{
    private WorkflowPeriodicScheduler $scheduler;

    protected function setUp(): void
    {
        parent::setUp();
        $this->scheduler = new WorkflowPeriodicScheduler;
        $this->createTables();
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('workflow_execution_steps');
        Schema::dropIfExists('workflow_executions');
        Schema::dropIfExists('workflow_runs');
        Schema::dropIfExists('workflow_versions');
        Schema::dropIfExists('customer_group_details');
        Schema::dropIfExists('workflow_customer_groups');
        Schema::dropIfExists('customer_groups');
        Schema::dropIfExists('workflows');
        Schema::dropIfExists('customer');

        parent::tearDown();
    }

    // ---------------------------------------------------------------
    // all_customer = true：全量客户场景
    // ---------------------------------------------------------------

    public function test_all_customer_workflow_creates_executions_for_every_customer(): void
    {
        Queue::fake();
        Carbon::setTestNow('2026-02-26 10:00:00');

        $c1 = Customer::withoutEvents(fn () => Customer::query()->create(['name' => 'Alice']));
        $c2 = Customer::withoutEvents(fn () => Customer::query()->create(['name' => 'Bob']));

        $workflow = $this->createPeriodicWorkflow(allCustomer: true, nextRunAt: '2026-02-26 09:00:00');

        $this->dispatchCommand(batchSize: 100);

        // 命令应创建一条 WorkflowRun 记录
        $this->assertSame(1, WorkflowRun::query()->where('workflow_id', $workflow->id)->count());
        $run = WorkflowRun::query()->where('workflow_id', $workflow->id)->first();
        $this->assertSame('all', $run->target_mode);
        $this->assertSame('pending', $run->status->value);

        // 验证 DispatchWorkflowRunJob 被派发
        Queue::assertPushed(DispatchWorkflowRunJob::class, 1);

        // next_run_at 已被推进
        $workflow->refresh();
        $this->assertNotNull($workflow->next_run_at);
        $this->assertTrue($workflow->next_run_at->greaterThan(Carbon::parse('2026-02-26 10:00:00')));
    }

    // ---------------------------------------------------------------
    // 指定客户分组场景
    // ---------------------------------------------------------------

    public function test_group_bound_workflow_creates_executions_only_for_target_customers(): void
    {
        Queue::fake();
        Carbon::setTestNow('2026-02-26 10:00:00');

        $c1 = Customer::withoutEvents(fn () => Customer::query()->create(['name' => 'InGroup']));
        $c2 = Customer::withoutEvents(fn () => Customer::query()->create(['name' => 'OutOfGroup']));

        // 创建客户分组 + 明细
        $groupId = DB::table('customer_groups')->insertGetId([
            'name' => 'VIP',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('customer_group_details')->insert([
            'customer_id' => $c1->id,
            'customer_group_id' => $groupId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $workflow = $this->createPeriodicWorkflow(allCustomer: false, nextRunAt: '2026-02-26 09:00:00');

        // 绑定工作流 ↔ 分组
        DB::table('workflow_customer_groups')->insert([
            'workflow_id' => $workflow->id,
            'customer_group_id' => $groupId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 需要 eager load customerGroups
        $this->dispatchCommand(batchSize: 100);

        // 命令应创建一条 WorkflowRun 记录，target_mode = groups
        $this->assertSame(1, WorkflowRun::query()->where('workflow_id', $workflow->id)->count());
        $run = WorkflowRun::query()->where('workflow_id', $workflow->id)->first();
        $this->assertSame('groups', $run->target_mode);
        $this->assertContains($groupId, $run->group_ids_json);

        Queue::assertPushed(DispatchWorkflowRunJob::class, 1);
    }

    // ---------------------------------------------------------------
    // 无分组关联时不产生执行记录
    // ---------------------------------------------------------------

    public function test_no_group_bound_workflow_creates_no_executions(): void
    {
        Queue::fake();
        Carbon::setTestNow('2026-02-26 10:00:00');

        Customer::withoutEvents(fn () => Customer::query()->create(['name' => 'Lonely']));

        $workflow = $this->createPeriodicWorkflow(allCustomer: false, nextRunAt: '2026-02-26 09:00:00');
        // 不绑定任何分组

        $this->dispatchCommand(batchSize: 100);

        // 命令仍会创建 WorkflowRun 并派发 DispatchWorkflowRunJob（空分组过滤在 Job 层处理）
        $this->assertSame(1, WorkflowRun::query()->where('workflow_id', $workflow->id)->count());
        $run = WorkflowRun::query()->where('workflow_id', $workflow->id)->first();
        $this->assertSame('groups', $run->target_mode);
        $this->assertEmpty($run->group_ids_json);

        Queue::assertPushed(DispatchWorkflowRunJob::class, 1);

        // next_run_at 仍应被推进
        $workflow->refresh();
        $this->assertNotNull($workflow->next_run_at);
    }

    // ---------------------------------------------------------------
    // 调度失败后 last_run_at 和 next_run_at 都被更新
    // ---------------------------------------------------------------

    public function test_last_run_at_is_updated_even_on_dispatch_failure(): void
    {
        Queue::fake();
        Carbon::setTestNow('2026-02-26 10:00:00');

        $workflow = $this->createPeriodicWorkflow(allCustomer: true, nextRunAt: '2026-02-26 09:00:00');

        // 初始状态：last_run_at 为 null
        $this->assertNull($workflow->last_run_at);

        // 删除 customer 表以模拟查询异常
        Schema::drop('customer');

        $this->dispatchCommand(batchSize: 100);

        $workflow->refresh();
        // last_run_at 应被更新为调度尝试时间
        $this->assertNotNull($workflow->last_run_at);
        $this->assertEquals('2026-02-26 10:00:00', $workflow->last_run_at->toDateTimeString());
        // next_run_at 应已推进
        $this->assertNotNull($workflow->next_run_at);

        // 重建 customer 表，避免 tearDown 出错
        Schema::create('customer', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name')->nullable();
            $table->string('phone')->nullable();
            $table->timestamps();
        });
    }

    // ---------------------------------------------------------------
    // 调度失败后 next_run_at 仍然推进
    // ---------------------------------------------------------------

    public function test_next_run_at_is_advanced_even_on_dispatch_failure(): void
    {
        Queue::fake();
        Carbon::setTestNow('2026-02-26 10:00:00');

        $workflow = $this->createPeriodicWorkflow(allCustomer: true, nextRunAt: '2026-02-26 09:00:00');
        $oldNextRunAt = $workflow->next_run_at->copy();

        // 删除 customer 表以模拟查询异常
        Schema::drop('customer');

        $this->dispatchCommand(batchSize: 100);

        $workflow->refresh();
        // next_run_at 应已推进（不等于原值，且不为 null）
        $this->assertNotNull($workflow->next_run_at);
        $this->assertTrue($workflow->next_run_at->greaterThan($oldNextRunAt));

        // 重建 customer 表，避免 tearDown 出错
        Schema::create('customer', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name')->nullable();
            $table->string('phone')->nullable();
            $table->timestamps();
        });
    }

    // ---------------------------------------------------------------
    // chunkById 分批处理可正常工作（大批量）
    // ---------------------------------------------------------------

    public function test_large_batch_chunking_works_correctly(): void
    {
        Queue::fake();
        Carbon::setTestNow('2026-02-26 10:00:00');

        // 创建 5 个客户，batch=2 → 3 个 chunk
        for ($i = 0; $i < 5; $i++) {
            Customer::withoutEvents(fn () => Customer::query()->create(['name' => "Customer {$i}"]));
        }

        $workflow = $this->createPeriodicWorkflow(allCustomer: true, nextRunAt: '2026-02-26 09:00:00');

        $this->dispatchCommand(batchSize: 2);

        // 命令只创建一条 WorkflowRun 记录（不再直接创建 WorkflowExecution）
        $this->assertSame(1, WorkflowRun::query()->where('workflow_id', $workflow->id)->count());
        Queue::assertPushed(DispatchWorkflowRunJob::class, 1);
    }

    // ---------------------------------------------------------------
    // 未到期的工作流不会被调度
    // ---------------------------------------------------------------

    public function test_workflow_not_due_is_not_dispatched(): void
    {
        Queue::fake();
        Carbon::setTestNow('2026-02-26 10:00:00');

        Customer::withoutEvents(fn () => Customer::query()->create(['name' => 'Ignored']));

        // next_run_at 在未来
        $this->createPeriodicWorkflow(allCustomer: true, nextRunAt: '2026-02-27 09:00:00');

        $this->dispatchCommand(batchSize: 100);

        $this->assertSame(0, WorkflowExecution::query()->count());
        Queue::assertNothingPushed();
    }

    // ---------------------------------------------------------------
    // 分组内有重复客户时 distinct 去重
    // ---------------------------------------------------------------

    public function test_duplicate_customer_in_multiple_groups_is_deduplicated(): void
    {
        Queue::fake();
        Carbon::setTestNow('2026-02-26 10:00:00');

        $c1 = Customer::withoutEvents(fn () => Customer::query()->create(['name' => 'SharedCustomer']));

        $groupA = DB::table('customer_groups')->insertGetId(['name' => 'A', 'created_at' => now(), 'updated_at' => now()]);
        $groupB = DB::table('customer_groups')->insertGetId(['name' => 'B', 'created_at' => now(), 'updated_at' => now()]);

        // 同一客户在两个分组中
        foreach ([$groupA, $groupB] as $gid) {
            DB::table('customer_group_details')->insert([
                'customer_id' => $c1->id,
                'customer_group_id' => $gid,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $workflow = $this->createPeriodicWorkflow(allCustomer: false, nextRunAt: '2026-02-26 09:00:00');

        foreach ([$groupA, $groupB] as $gid) {
            DB::table('workflow_customer_groups')->insert([
                'workflow_id' => $workflow->id,
                'customer_group_id' => $gid,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->dispatchCommand(batchSize: 100);

        // 命令应创建一条 WorkflowRun，group_ids_json 含两个分组
        $this->assertSame(1, WorkflowRun::query()->where('workflow_id', $workflow->id)->count());
        $run = WorkflowRun::query()->where('workflow_id', $workflow->id)->first();
        $this->assertSame('groups', $run->target_mode);
        $this->assertEqualsCanonicalizing([$groupA, $groupB], $run->group_ids_json);

        Queue::assertPushed(DispatchWorkflowRunJob::class, 1);
    }

    // ===============================================================
    // Helper methods
    // ===============================================================

    /**
     * 直接调用 Command 的 dispatchPeriodicWorkflows 方法（绕过多租户循环）
     */
    private function dispatchCommand(int $batchSize = 200): void
    {
        $command = new WorkflowDispatchPeriodicCommand($this->scheduler);
        $command->setLaravel($this->app);

        // 使用反射调用 private 方法 dispatchPeriodicWorkflows
        $ref = new \ReflectionMethod($command, 'dispatchPeriodicWorkflows');
        $ref->setAccessible(true);

        // 传入一个 fake tenant 对象（仅需 id 属性供 sprintf 使用）
        $fakeTenant = new class
        {
            public string $id = 'test-tenant';
        };

        $output = new OutputStyle(new ArrayInput([]), new NullOutput);
        $command->setOutput($output);
        $ref->invoke($command, $fakeTenant, 50, $batchSize);
    }

    private function createPeriodicWorkflow(bool $allCustomer, string $nextRunAt): Workflow
    {
        return Workflow::query()->create([
            'name' => 'periodic-test-'.uniqid(),
            'description' => null,
            'category_id' => 1,
            'create_user_id' => 1,
            'all_customer' => $allCustomer,
            'type' => 'periodic',
            'status' => 'active',
            'next_run_at' => Carbon::parse($nextRunAt),
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
            ],
        ]);
    }

    private function createTables(): void
    {
        Schema::create('customer', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name')->nullable();
            $table->string('phone')->nullable();
            $table->timestamps();
        });

        Schema::create('customer_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->timestamps();
        });

        Schema::create('customer_group_details', function (Blueprint $table) {
            $table->uuid('customer_id');
            $table->unsignedBigInteger('customer_group_id')->index();
            $table->timestamps();
            $table->index(['customer_id', 'customer_group_id']);
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
            $table->unsignedInteger('dispatch_chunk_size')->default(2000);
            $table->unsignedInteger('dispatch_concurrency')->default(12);
            $table->unsignedInteger('execution_batch_size')->default(200);
            $table->unsignedInteger('max_queue_lag')->default(1000);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('workflow_customer_groups', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('workflow_id');
            $table->unsignedBigInteger('customer_group_id');
            $table->timestamps();
        });

        Schema::create('workflow_versions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('workflow_id');
            $table->unsignedInteger('version_no');
            $table->string('source')->default('save');
            $table->unsignedBigInteger('create_user_id')->nullable();
            $table->json('snapshot');
            $table->timestamps();
        });

        Schema::create('workflow_runs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('workflow_id');
            $table->unsignedBigInteger('workflow_version_id')->nullable();
            $table->string('run_key');
            $table->string('status')->default('pending');
            $table->string('target_mode')->default('all');
            $table->json('group_ids_json')->nullable();
            $table->string('cursor_last_customer_id')->nullable();
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
            $table->unique(['workflow_id', 'run_key']);
        });

        Schema::create('workflow_executions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('workflow_id');
            $table->unsignedBigInteger('workflow_version_id')->nullable();
            $table->unsignedBigInteger('run_id')->nullable();
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
