<?php

namespace Tests\Feature\Workflow;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class WorkflowExecutionControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware();
        $this->createWorkflowExecutionTables();
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('workflow_execution_steps');
        Schema::dropIfExists('workflow_executions');
        Schema::dropIfExists('workflow_versions');
        Schema::dropIfExists('users');
        Schema::dropIfExists('workflows');

        parent::tearDown();
    }

    public function test_index_returns_rows_and_total(): void
    {
        $this->seedBaseData();
        $this->insertExecution([
            'id' => 1001,
            'workflow_id' => 1,
            'status' => 'running',
            'started_at' => '2026-02-24 09:00:00',
            'trigger_type' => 'event',
            'trigger_user_id' => 1,
        ]);
        $this->insertExecution([
            'id' => 1002,
            'workflow_id' => 2,
            'status' => 'success',
            'started_at' => '2026-02-24 10:00:00',
            'trigger_type' => 'manual',
            'trigger_user_id' => 1,
        ]);

        $response = $this->getJson('/workflow/execution/index?rows=10');

        $response->assertOk();
        $response->assertJsonPath('code', 200);
        $response->assertJsonPath('data.total', 2);
        $response->assertJsonCount(2, 'data.rows');
    }

    public function test_workflows_returns_dropdown_options_and_supports_keyword(): void
    {
        $this->seedBaseData();
        DB::table('workflows')->insert([
            'id' => 3,
            'name' => 'Another Workflow',
            'created_at' => now(),
            'updated_at' => now(),
            'deleted_at' => null,
        ]);

        $response = $this->getJson('/workflow/execution/workflows?keyword=Workflow A&rows=20');

        $response->assertOk();
        $response->assertJsonPath('code', 200);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.name', 'Workflow A');
    }

    public function test_workflows_returns_all_options_without_limit(): void
    {
        $this->seedBaseData();
        DB::table('workflows')->insert([
            'id' => 3,
            'name' => 'Workflow C',
            'created_at' => now(),
            'updated_at' => now(),
            'deleted_at' => null,
        ]);

        $response = $this->getJson('/workflow/execution/workflows');

        $response->assertOk();
        $response->assertJsonPath('code', 200);
        $response->assertJsonCount(3, 'data');
    }

    public function test_index_filters_by_workflow_and_status(): void
    {
        $this->seedBaseData();
        $this->insertExecution([
            'id' => 1101,
            'workflow_id' => 1,
            'status' => 'running',
            'started_at' => '2026-02-24 09:00:00',
            'trigger_type' => 'event',
        ]);
        $this->insertExecution([
            'id' => 1102,
            'workflow_id' => 1,
            'status' => 'success',
            'started_at' => '2026-02-24 10:00:00',
            'trigger_type' => 'event',
        ]);
        $this->insertExecution([
            'id' => 1103,
            'workflow_id' => 2,
            'status' => 'running',
            'started_at' => '2026-02-24 11:00:00',
            'trigger_type' => 'manual',
        ]);

        $response = $this->getJson('/workflow/execution/index?workflow_id=1&status=running');

        $response->assertOk();
        $response->assertJsonPath('code', 200);
        $response->assertJsonPath('data.total', 1);
        $response->assertJsonPath('data.rows.0.id', 1101);
    }

    public function test_index_filters_by_execution_id(): void
    {
        $this->seedBaseData();
        $this->insertExecution([
            'id' => 1111,
            'workflow_id' => 1,
            'status' => 'running',
            'started_at' => '2026-02-24 09:00:00',
        ]);
        $this->insertExecution([
            'id' => 1112,
            'workflow_id' => 2,
            'status' => 'success',
            'started_at' => '2026-02-24 10:00:00',
        ]);

        $response = $this->getJson('/workflow/execution/index?execution_id=1112');

        $response->assertOk();
        $response->assertJsonPath('code', 200);
        $response->assertJsonPath('data.total', 1);
        $response->assertJsonPath('data.rows.0.id', 1112);
    }

    public function test_index_returns_empty_when_execution_id_and_workflow_id_do_not_match(): void
    {
        $this->seedBaseData();
        $this->insertExecution([
            'id' => 1113,
            'workflow_id' => 1,
            'status' => 'running',
            'started_at' => '2026-02-24 09:00:00',
        ]);

        $response = $this->getJson('/workflow/execution/index?execution_id=1113&workflow_id=2');

        $response->assertOk();
        $response->assertJsonPath('code', 200);
        $response->assertJsonPath('data.total', 0);
        $response->assertJsonCount(0, 'data.rows');
    }

    public function test_index_filters_by_date_range_with_day_boundaries(): void
    {
        $this->seedBaseData();
        $this->insertExecution([
            'id' => 1201,
            'workflow_id' => 1,
            'status' => 'success',
            'started_at' => '2026-02-20 00:00:00',
        ]);
        $this->insertExecution([
            'id' => 1202,
            'workflow_id' => 1,
            'status' => 'success',
            'started_at' => '2026-02-20 23:59:59',
        ]);
        $this->insertExecution([
            'id' => 1203,
            'workflow_id' => 1,
            'status' => 'success',
            'started_at' => '2026-02-21 00:00:00',
        ]);

        $response = $this->getJson('/workflow/execution/index?start_date=2026-02-20&end_date=2026-02-20&sort=id&order=asc');

        $response->assertOk();
        $response->assertJsonPath('code', 200);
        $response->assertJsonPath('data.total', 2);
        $response->assertJsonPath('data.rows.0.id', 1201);
        $response->assertJsonPath('data.rows.1.id', 1202);
    }

    public function test_index_falls_back_to_default_sort_when_sort_is_invalid(): void
    {
        $this->seedBaseData();
        $this->insertExecution([
            'id' => 1301,
            'workflow_id' => 1,
            'status' => 'success',
            'started_at' => '2026-02-24 09:00:00',
        ]);
        $this->insertExecution([
            'id' => 1302,
            'workflow_id' => 1,
            'status' => 'success',
            'started_at' => '2026-02-24 12:00:00',
        ]);

        $response = $this->getJson('/workflow/execution/index?sort=not_exists_field');

        $response->assertOk();
        $response->assertJsonPath('code', 200);
        $response->assertJsonPath('data.rows.0.id', 1302);
    }

    public function test_index_falls_back_to_desc_when_order_is_invalid(): void
    {
        $this->seedBaseData();
        $this->insertExecution([
            'id' => 1401,
            'workflow_id' => 1,
            'status' => 'success',
            'started_at' => '2026-02-24 09:00:00',
        ]);
        $this->insertExecution([
            'id' => 1402,
            'workflow_id' => 1,
            'status' => 'success',
            'started_at' => '2026-02-24 10:00:00',
        ]);

        $response = $this->getJson('/workflow/execution/index?sort=id&order=not-valid');

        $response->assertOk();
        $response->assertJsonPath('code', 200);
        $response->assertJsonPath('data.rows.0.id', 1402);
        $response->assertJsonPath('data.rows.1.id', 1401);
    }

    public function test_index_filters_latest_version_only_for_workflow(): void
    {
        $this->seedBaseData();
        $this->insertWorkflowVersion([
            'id' => 2101,
            'workflow_id' => 1,
            'version_no' => 1,
        ]);
        $this->insertWorkflowVersion([
            'id' => 2102,
            'workflow_id' => 1,
            'version_no' => 2,
        ]);

        $this->insertExecution([
            'id' => 1701,
            'workflow_id' => 1,
            'workflow_version_id' => 2101,
            'started_at' => '2026-02-24 09:00:00',
        ]);
        $this->insertExecution([
            'id' => 1702,
            'workflow_id' => 1,
            'workflow_version_id' => 2102,
            'started_at' => '2026-02-24 10:00:00',
        ]);

        $response = $this->getJson('/workflow/execution/index?workflow_id=1&latest_version_only=1&sort=id&order=asc');

        $response->assertOk();
        $response->assertJsonPath('code', 200);
        $response->assertJsonPath('data.total', 1);
        $response->assertJsonPath('data.rows.0.id', 1702);
    }

    public function test_index_filters_by_workflow_version_id(): void
    {
        $this->seedBaseData();
        $this->insertWorkflowVersion([
            'id' => 2201,
            'workflow_id' => 1,
            'version_no' => 1,
        ]);
        $this->insertWorkflowVersion([
            'id' => 2202,
            'workflow_id' => 1,
            'version_no' => 2,
        ]);

        $this->insertExecution([
            'id' => 1711,
            'workflow_id' => 1,
            'workflow_version_id' => 2201,
            'started_at' => '2026-02-24 09:00:00',
        ]);
        $this->insertExecution([
            'id' => 1712,
            'workflow_id' => 1,
            'workflow_version_id' => 2202,
            'started_at' => '2026-02-24 10:00:00',
        ]);

        $response = $this->getJson('/workflow/execution/index?workflow_id=1&workflow_version_id=2201');

        $response->assertOk();
        $response->assertJsonPath('code', 200);
        $response->assertJsonPath('data.total', 1);
        $response->assertJsonPath('data.rows.0.id', 1711);
    }

    public function test_index_prioritizes_workflow_version_id_over_latest_version_only(): void
    {
        $this->seedBaseData();
        $this->insertWorkflowVersion([
            'id' => 2301,
            'workflow_id' => 1,
            'version_no' => 1,
        ]);
        $this->insertWorkflowVersion([
            'id' => 2302,
            'workflow_id' => 1,
            'version_no' => 2,
        ]);

        $this->insertExecution([
            'id' => 1721,
            'workflow_id' => 1,
            'workflow_version_id' => 2301,
            'started_at' => '2026-02-24 09:00:00',
        ]);
        $this->insertExecution([
            'id' => 1722,
            'workflow_id' => 1,
            'workflow_version_id' => 2302,
            'started_at' => '2026-02-24 10:00:00',
        ]);

        $response = $this->getJson('/workflow/execution/index?workflow_id=1&workflow_version_id=2301&latest_version_only=1');

        $response->assertOk();
        $response->assertJsonPath('code', 200);
        $response->assertJsonPath('data.total', 1);
        $response->assertJsonPath('data.rows.0.id', 1721);
    }

    public function test_index_returns_empty_rows_when_latest_version_only_and_no_version_exists(): void
    {
        $this->seedBaseData();
        $this->insertExecution([
            'id' => 1731,
            'workflow_id' => 1,
            'workflow_version_id' => null,
            'started_at' => '2026-02-24 09:00:00',
        ]);

        $response = $this->getJson('/workflow/execution/index?workflow_id=1&latest_version_only=1');

        $response->assertOk();
        $response->assertJsonPath('code', 200);
        $response->assertJsonPath('data.total', 0);
        $response->assertJsonCount(0, 'data.rows');
    }

    public function test_index_validates_workflow_version_id_exists(): void
    {
        $this->seedBaseData();

        $response = $this->getJson('/workflow/execution/index?workflow_version_id=999999');

        $response->assertOk();
        $response->assertJsonPath('code', 400);
        $response->assertJsonPath('msg', '指定的工作流版本不存在，请刷新后重试');
    }

    public function test_index_validates_execution_id_exists(): void
    {
        $this->seedBaseData();

        $response = $this->getJson('/workflow/execution/index?execution_id=999999');

        $response->assertOk();
        $response->assertJsonPath('code', 400);
        $response->assertJsonPath('msg', '指定的执行记录不存在，请刷新后重试');
    }

    public function test_detail_returns_workflow_version_snapshot_and_sorted_steps(): void
    {
        $this->seedBaseData();
        DB::table('workflow_versions')->insert([
            'id' => 2001,
            'workflow_id' => 1,
            'version_no' => 3,
            'source' => 'publish',
            'create_user_id' => 1,
            'snapshot' => json_encode([
                'name' => 'Workflow A v3',
                'rule_chain' => [
                    'nodes' => [
                        ['id' => 'start-node', 'type' => 'start_trigger'],
                        ['id' => 'end-node', 'type' => 'end'],
                    ],
                    'connections' => [
                        ['id' => 'line-1', 'source' => 'start-node', 'target' => 'end-node', 'type' => 'main'],
                    ],
                    'layout' => [
                        'mode' => 'auto',
                        'laneOrder' => ['lane_main'],
                        'flow' => [
                            ['nodeId' => 'start-node', 'lane' => 'lane_main', 'order' => 0],
                            ['nodeId' => 'end-node', 'lane' => 'lane_main', 'order' => 1],
                        ],
                    ],
                ],
            ], JSON_UNESCAPED_UNICODE),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->insertExecution([
            'id' => 1601,
            'workflow_id' => 1,
            'workflow_version_id' => 2001,
            'status' => 'success',
            'started_at' => '2026-02-24 10:00:00',
            'finished_at' => '2026-02-24 10:00:05',
            'duration' => 5000,
            'trigger_user_id' => 1,
        ]);

        DB::table('workflow_execution_steps')->insert([
            [
                'id' => 5002,
                'workflow_execution_id' => 1601,
                'workflow_version_id' => 2001,
                'node_id' => 'end-node',
                'node_type' => 'end',
                'node_name' => '结束',
                'status' => 'success',
                'attempt' => 1,
                'started_at' => '2026-02-24 10:00:04',
                'finished_at' => '2026-02-24 10:00:05',
                'duration_ms' => 1000,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 5001,
                'workflow_execution_id' => 1601,
                'workflow_version_id' => 2001,
                'node_id' => 'start-node',
                'node_type' => 'start_trigger',
                'node_name' => '开始',
                'status' => 'success',
                'attempt' => 1,
                'started_at' => '2026-02-24 10:00:00',
                'finished_at' => '2026-02-24 10:00:01',
                'duration_ms' => 1000,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $response = $this->getJson('/workflow/execution/detail?id=1601');

        $response->assertOk();
        $response->assertJsonPath('code', 200);
        $response->assertJsonPath('data.id', 1601);
        $response->assertJsonPath('data.workflow_version.id', 2001);
        $response->assertJsonPath('data.workflow_version.version_no', 3);
        $response->assertJsonPath('data.workflow_version.snapshot.name', 'Workflow A v3');
        $response->assertJsonPath('data.steps.0.id', 5001);
        $response->assertJsonPath('data.steps.1.id', 5002);
    }

    public function test_detail_includes_resolved_input_with_upstream_data(): void
    {
        $this->seedBaseData();

        $this->insertExecution([
            'id' => 1801,
            'workflow_id' => 1,
            'status' => 'success',
            'started_at' => '2026-02-24 10:00:00',
            'finished_at' => '2026-02-24 10:00:10',
            'context_data' => json_encode([
                'runtime' => [
                    'node_outputs' => [
                        'node-1' => ['customer_id' => 12345, 'customer_name' => 'John Doe'],
                        'node-2' => ['order_id' => 67890, 'total' => 999.99],
                    ],
                ],
            ], JSON_UNESCAPED_UNICODE),
        ]);

        DB::table('workflow_execution_steps')->insert([
            [
                'id' => 6001,
                'workflow_execution_id' => 1801,
                'node_id' => 'node-1',
                'node_type' => 'query',
                'node_name' => '查询客户',
                'status' => 'success',
                'input_data' => json_encode([
                    'parameters' => ['customer_id' => 12345],
                ], JSON_UNESCAPED_UNICODE),
                'output_data' => json_encode(['customer_id' => 12345, 'customer_name' => 'John Doe'], JSON_UNESCAPED_UNICODE),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 6002,
                'workflow_execution_id' => 1801,
                'node_id' => 'node-2',
                'node_type' => 'query',
                'node_name' => '查询订单',
                'status' => 'success',
                'input_data' => json_encode([
                    'parameters' => ['order_id' => 67890],
                    'from_node_id' => 'node-1',
                    'from_node_name' => '查询客户',
                ], JSON_UNESCAPED_UNICODE),
                'output_data' => json_encode(['order_id' => 67890, 'total' => 999.99], JSON_UNESCAPED_UNICODE),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $response = $this->getJson('/workflow/execution/detail?id=1801');

        $response->assertOk();
        $response->assertJsonPath('code', 200);

        // 验证第一个步骤的 resolved_input（无上游节点）
        $response->assertJsonPath('data.steps.0.resolved_input.parameters.customer_id', 12345);
        $response->assertJsonPath('data.steps.0.resolved_input.from_node_id', null);
        $response->assertJsonPath('data.steps.0.resolved_input.data', null);

        // 验证第二个步骤的 resolved_input（有上游节点数据）
        $response->assertJsonPath('data.steps.1.resolved_input.parameters.order_id', 67890);
        $response->assertJsonPath('data.steps.1.resolved_input.from_node_id', 'node-1');
        $response->assertJsonPath('data.steps.1.resolved_input.from_node_name', '查询客户');
        $response->assertJsonPath('data.steps.1.resolved_input.data.customer_id', 12345);
        $response->assertJsonPath('data.steps.1.resolved_input.data.customer_name', 'John Doe');
    }

    private function seedBaseData(): void
    {
        DB::table('workflows')->insert([
            ['id' => 1, 'name' => 'Workflow A', 'rule_chain' => null, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'name' => 'Workflow B', 'rule_chain' => null, 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('users')->insert([
            'id' => 1,
            'name' => 'Tester',
            'email' => 'tester@example.com',
            'password' => 'secret',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function insertExecution(array $overrides = []): void
    {
        DB::table('workflow_executions')->insert(array_merge([
            'id' => null,
            'workflow_id' => 1,
            'workflow_version_id' => null,
            'status' => 'running',
            'started_at' => '2026-02-24 00:00:00',
            'finished_at' => null,
            'duration' => null,
            'input_data' => null,
            'output_data' => null,
            'error_message' => null,
            'execution_data' => null,
            'current_node_id' => null,
            'next_node_id' => null,
            'context_data' => null,
            'waiting_until' => null,
            'trigger_event' => null,
            'trigger_model_type' => null,
            'trigger_model_id' => null,
            'lock_version' => 0,
            'trigger_type' => null,
            'trigger_user_id' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ], $overrides));
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function insertWorkflowVersion(array $overrides = []): void
    {
        DB::table('workflow_versions')->insert(array_merge([
            'id' => null,
            'workflow_id' => 1,
            'version_no' => 1,
            'source' => 'save',
            'create_user_id' => 1,
            'snapshot' => json_encode([
                'name' => 'Workflow Snapshot',
                'rule_chain' => [
                    'nodes' => [],
                    'connections' => [],
                ],
            ], JSON_UNESCAPED_UNICODE),
            'created_at' => now(),
            'updated_at' => now(),
        ], $overrides));
    }

    private function createWorkflowExecutionTables(): void
    {
        Schema::create('workflows', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->json('rule_chain')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('password')->nullable();
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
