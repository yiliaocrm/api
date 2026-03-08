<?php

namespace Tests\Feature\Workflow;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class WorkflowControllerValidationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware();
        $this->createWorkflowTables();
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('workflows');

        parent::tearDown();
    }

    public function test_remove_rejects_active_workflow(): void
    {
        $this->insertWorkflow([
            'id' => 1,
            'type' => 'trigger',
            'status' => 'active',
            'rule_chain' => [
                'nodes' => [
                    ['id' => 'start', 'type' => 'start_trigger'],
                    ['id' => 'end', 'type' => 'end'],
                ],
                'connections' => [
                    ['source' => 'start', 'target' => 'end', 'type' => 'main'],
                ],
            ],
        ]);

        $response = $this->getJson('/workflow/remove?id=1');

        $response->assertOk();
        $response->assertJsonPath('code', 400);
        $response->assertJsonPath('msg', '已发布状态的工作流不可删除，请先取消发布');
    }

    public function test_activate_rejects_when_rule_chain_is_empty(): void
    {
        $this->insertWorkflow([
            'id' => 2,
            'type' => 'trigger',
            'status' => 'paused',
            'rule_chain' => null,
        ]);

        $response = $this->postJson('/workflow/activate', ['id' => 2]);

        $response->assertOk();
        $response->assertJsonPath('code', 400);
        $response->assertJsonPath('msg', '工作流没有配置规则链，无法发布');
    }

    public function test_activate_rejects_when_periodic_config_cannot_be_extracted(): void
    {
        $this->insertWorkflow([
            'id' => 3,
            'type' => 'periodic',
            'status' => 'paused',
            'rule_chain' => [
                'nodes' => [
                    ['id' => 'start', 'type' => 'START_PERIODIC'],
                    ['id' => 'end', 'type' => 'end'],
                ],
                'connections' => [
                    ['source' => 'start', 'target' => 'end', 'type' => 'main'],
                ],
            ],
        ]);

        $response = $this->postJson('/workflow/activate', ['id' => 3]);

        $response->assertOk();
        $response->assertJsonPath('code', 400);
        $response->assertJsonPath('msg', '周期型工作流缺少开始节点配置');
    }

    public function test_activate_rejects_when_wait_node_directly_points_to_wait_node(): void
    {
        $this->insertWorkflow([
            'id' => 5,
            'type' => 'trigger',
            'status' => 'paused',
            'rule_chain' => [
                'nodes' => [
                    ['id' => 'start', 'type' => 'start_trigger'],
                    ['id' => 'wait_1', 'type' => 'wait'],
                    ['id' => 'wait_2', 'type' => 'wait'],
                    ['id' => 'end', 'type' => 'end'],
                ],
                'connections' => [
                    ['source' => 'start', 'target' => 'wait_1', 'type' => 'main'],
                    ['source' => 'wait_1', 'target' => 'wait_2', 'type' => 'main'],
                    ['source' => 'wait_2', 'target' => 'end', 'type' => 'main'],
                ],
            ],
        ]);

        $response = $this->postJson('/workflow/activate', ['id' => 5]);

        $response->assertOk();
        $response->assertJsonPath('code', 400);
        $response->assertJsonPath('msg', '等待节点后不能直接连接等待节点');
    }

    public function test_activate_allows_wait_nodes_with_non_wait_between(): void
    {
        $this->insertWorkflow([
            'id' => 6,
            'type' => 'trigger',
            'status' => 'paused',
            'rule_chain' => [
                'nodes' => [
                    ['id' => 'start', 'type' => 'start_trigger'],
                    ['id' => 'wait_1', 'type' => 'wait'],
                    ['id' => 'log_1', 'type' => 'log'],
                    ['id' => 'wait_2', 'type' => 'wait'],
                    ['id' => 'end', 'type' => 'end'],
                ],
                'connections' => [
                    ['source' => 'start', 'target' => 'wait_1', 'type' => 'main'],
                    ['source' => 'wait_1', 'target' => 'log_1', 'type' => 'main'],
                    ['source' => 'log_1', 'target' => 'wait_2', 'type' => 'main'],
                    ['source' => 'wait_2', 'target' => 'end', 'type' => 'main'],
                ],
            ],
        ]);

        $response = $this->postJson('/workflow/activate', ['id' => 6]);

        $response->assertOk();
        $response->assertJsonPath('code', 200);
    }

    public function test_activate_rejects_when_multiple_consecutive_wait_nodes_exist(): void
    {
        $this->insertWorkflow([
            'id' => 7,
            'type' => 'trigger',
            'status' => 'paused',
            'rule_chain' => [
                'nodes' => [
                    ['id' => 'start', 'type' => 'start_trigger'],
                    ['id' => 'wait_1', 'type' => 'wait'],
                    ['id' => 'wait_2', 'type' => 'wait'],
                    ['id' => 'wait_3', 'type' => 'wait'],
                    ['id' => 'end', 'type' => 'end'],
                ],
                'connections' => [
                    ['source' => 'start', 'target' => 'wait_1', 'type' => 'main'],
                    ['source' => 'wait_1', 'target' => 'wait_2', 'type' => 'main'],
                    ['source' => 'wait_2', 'target' => 'wait_3', 'type' => 'main'],
                    ['source' => 'wait_3', 'target' => 'end', 'type' => 'main'],
                ],
            ],
        ]);

        $response = $this->postJson('/workflow/activate', ['id' => 7]);

        $response->assertOk();
        $response->assertJsonPath('code', 400);
        $response->assertJsonPath('msg', '等待节点后不能直接连接等待节点');
    }

    public function test_activate_rejects_when_wait_to_wait_exists_in_if_branch_path(): void
    {
        $this->insertWorkflow([
            'id' => 8,
            'type' => 'trigger',
            'status' => 'paused',
            'rule_chain' => [
                'nodes' => [
                    ['id' => 'start', 'type' => 'start_trigger'],
                    [
                        'id' => 'if_1',
                        'type' => 'if',
                        'parameters' => [
                            'matchType' => 'all',
                            'rules' => [
                                [
                                    'leftType' => 'path',
                                    'leftValue' => 'trigger.model_id',
                                    'operator' => 'eq',
                                    'rightType' => 'literal',
                                    'rightValue' => '1',
                                ],
                            ],
                        ],
                    ],
                    ['id' => 'wait_true_1', 'type' => 'wait'],
                    ['id' => 'wait_true_2', 'type' => 'wait'],
                    ['id' => 'end_true', 'type' => 'end'],
                    ['id' => 'end_false', 'type' => 'end'],
                ],
                'connections' => [
                    ['source' => 'start', 'target' => 'if_1', 'type' => 'main'],
                    ['source' => 'if_1', 'target' => 'wait_true_1', 'type' => 'branch', 'sourcePort' => 'true'],
                    ['source' => 'if_1', 'target' => 'end_false', 'type' => 'branch', 'sourcePort' => 'false'],
                    ['source' => 'wait_true_1', 'target' => 'wait_true_2', 'type' => 'main'],
                    ['source' => 'wait_true_2', 'target' => 'end_true', 'type' => 'main'],
                ],
            ],
        ]);

        $response = $this->postJson('/workflow/activate', ['id' => 8]);

        $response->assertOk();
        $response->assertJsonPath('code', 400);
        $response->assertJsonPath('msg', '等待节点后不能直接连接等待节点');
    }

    public function test_batch_preview_validates_rule_chain_type(): void
    {
        $response = $this->postJson('/workflow/batch-preview', [
            'rule_chain' => 'invalid',
        ]);

        $response->assertOk();
        $response->assertJsonPath('code', 400);
        $response->assertJsonPath('msg', '批量预览失败：规则链格式无效');
    }

    public function test_trigger_sample_data_requires_event(): void
    {
        $response = $this->getJson('/workflow/trigger-sample-data');

        $response->assertOk();
        $response->assertJsonPath('code', 400);
        $response->assertJsonPath('msg', '请提供事件类型');
    }

    public function test_invalidate_preview_data_requires_workflow_id(): void
    {
        $response = $this->postJson('/workflow/invalidate-preview-data', [
            'node_ids' => ['node-1'],
        ]);

        $response->assertOk();
        $response->assertJsonPath('code', 400);
        $response->assertJsonPath('msg', '请提供工作流ID');
    }

    public function test_invalidate_preview_data_rejects_non_existing_workflow(): void
    {
        $response = $this->postJson('/workflow/invalidate-preview-data', [
            'workflow_id' => 9999,
            'node_ids' => ['node-1'],
        ]);

        $response->assertOk();
        $response->assertJsonPath('code', 400);
        $response->assertJsonPath('msg', '工作流不存在');
    }

    public function test_invalidate_preview_data_requires_non_empty_node_ids(): void
    {
        $this->insertWorkflow([
            'id' => 4,
            'type' => 'trigger',
            'status' => 'paused',
        ]);

        $response = $this->postJson('/workflow/invalidate-preview-data', [
            'workflow_id' => 4,
            'node_ids' => [],
        ]);

        $response->assertOk();
        $response->assertJsonPath('code', 400);
        $response->assertJsonPath('msg', '请提供要清除的节点ID列表');
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function insertWorkflow(array $overrides): void
    {
        $data = array_merge([
            'name' => 'Validation Workflow',
            'description' => 'workflow validation test',
            'category_id' => 1,
            'create_user_id' => 1,
            'workflow_id' => null,
            'active' => false,
            'all_customer' => false,
            'type' => 'trigger',
            'status' => 'paused',
            'cron' => null,
            'last_run_at' => null,
            'next_run_at' => null,
            'version' => '1.0.0',
            'rule_chain' => [
                'nodes' => [
                    ['id' => 'start', 'type' => 'start_trigger'],
                    ['id' => 'end', 'type' => 'end'],
                ],
                'connections' => [
                    ['source' => 'start', 'target' => 'end', 'type' => 'main'],
                ],
            ],
            'created_at' => now(),
            'updated_at' => now(),
            'deleted_at' => null,
        ], $overrides);

        if (array_key_exists('rule_chain', $data)) {
            $data['rule_chain'] = $data['rule_chain'] === null
                ? null
                : json_encode($data['rule_chain'], JSON_UNESCAPED_UNICODE);
        }

        DB::table('workflows')->insert($data);
    }

    private function createWorkflowTables(): void
    {
        Schema::create('workflows', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->text('description')->nullable();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->unsignedBigInteger('create_user_id')->nullable();
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
    }
}
