<?php

namespace Tests\Feature\Workflow;

use App\Enums\WorkflowStatus;
use App\Enums\WorkflowType;
use App\Models\Workflow;
use App\Models\WorkflowVersion;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class WorkflowScheduleSyncOnSaveTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware();
        $this->createWorkflowTables();
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('admin_parameters');
        Schema::dropIfExists('workflow_versions');
        Schema::dropIfExists('workflow_customer_groups');
        Schema::dropIfExists('customer_groups');
        Schema::dropIfExists('workflow_categories');
        Schema::dropIfExists('users');
        Schema::dropIfExists('workflows');

        parent::tearDown();
    }

    public function test_update_periodic_workflow_recalculates_cron_on_save(): void
    {
        $workflow = $this->createWorkflow([
            'type' => WorkflowType::PERIODIC->value,
            'status' => WorkflowStatus::PAUSED->value,
            'rule_chain' => $this->periodicRuleChain('09:00'),
            'cron' => ['runTime' => 'day', 'executeTime' => '09:00', 'dayInterval' => 1],
        ]);

        $response = $this->postJson('/workflow/update', [
            'id' => $workflow->id,
            'rule_chain' => $this->periodicRuleChain('23:10'),
            'type' => WorkflowType::PERIODIC->value,
        ]);

        $response->assertOk();
        $response->assertJsonPath('code', 200);

        $workflow->refresh();
        $this->assertSame('23:10', $workflow->cron['executeTime'] ?? null);
        $this->assertSame('day', $workflow->cron['runTime'] ?? null);
        $this->assertNull($workflow->next_run_at);
    }

    public function test_update_active_periodic_workflow_refreshes_next_run_at(): void
    {
        $executeTime = now()->addMinutes(5)->format('H:i');
        $workflow = $this->createWorkflow([
            'type' => WorkflowType::PERIODIC->value,
            'status' => WorkflowStatus::ACTIVE->value,
            'rule_chain' => $this->periodicRuleChain('09:00'),
            'cron' => ['runTime' => 'day', 'executeTime' => '09:00', 'dayInterval' => 1],
            'next_run_at' => now()->subDay(),
        ]);

        $response = $this->postJson('/workflow/update', [
            'id' => $workflow->id,
            'rule_chain' => $this->periodicRuleChain($executeTime),
            'type' => WorkflowType::PERIODIC->value,
        ]);

        $response->assertOk();
        $response->assertJsonPath('code', 200);

        $workflow->refresh();
        $this->assertSame($executeTime, $workflow->cron['executeTime'] ?? null);
        $this->assertNotNull($workflow->next_run_at);
        $this->assertTrue($workflow->next_run_at->greaterThan(now()->subSecond()));
    }

    public function test_update_trigger_workflow_clears_schedule_fields(): void
    {
        $workflow = $this->createWorkflow([
            'type' => WorkflowType::TRIGGER->value,
            'status' => WorkflowStatus::PAUSED->value,
            'cron' => ['runTime' => 'day', 'executeTime' => '08:00', 'dayInterval' => 1],
            'next_run_at' => now()->addHour(),
        ]);

        $response = $this->postJson('/workflow/update', [
            'id' => $workflow->id,
            'name' => 'Trigger Workflow Updated',
        ]);

        $response->assertOk();
        $response->assertJsonPath('code', 200);

        $workflow->refresh();
        $this->assertNull($workflow->cron);
        $this->assertNull($workflow->next_run_at);
    }

    public function test_history_restore_recalculates_periodic_schedule_from_rule_chain(): void
    {
        $workflow = $this->createWorkflow([
            'type' => WorkflowType::TRIGGER->value,
            'status' => WorkflowStatus::ACTIVE->value,
            'rule_chain' => $this->triggerRuleChain(),
            'cron' => null,
            'next_run_at' => null,
        ]);

        $version = WorkflowVersion::query()->create([
            'workflow_id' => $workflow->id,
            'version_no' => 1,
            'source' => 'save',
            'create_user_id' => 1,
            'snapshot' => [
                'workflow_id' => $workflow->id,
                'name' => 'Restored Periodic Workflow',
                'description' => 'restored periodic',
                'category_id' => 1,
                'type' => WorkflowType::PERIODIC->value,
                'all_customer' => false,
                'customer_group_ids' => [],
                'cron' => ['runTime' => 'day', 'executeTime' => '00:00', 'dayInterval' => 1],
                'rule_chain' => $this->periodicRuleChain('21:15'),
                'status' => WorkflowStatus::PAUSED->value,
                'saved_at' => now()->toDateTimeString(),
            ],
        ]);

        $response = $this->postJson('/workflow/history-restore', ['id' => $version->id]);

        $response->assertOk();
        $response->assertJsonPath('code', 200);

        $workflow->refresh();
        $this->assertSame(WorkflowType::PERIODIC, $workflow->type);
        $this->assertSame('21:15', $workflow->cron['executeTime'] ?? null);
        $this->assertNotNull($workflow->next_run_at);
        $this->assertTrue($workflow->next_run_at->greaterThan(now()->subSecond()));
    }

    public function test_update_periodic_workflow_returns_error_when_start_node_missing(): void
    {
        $workflow = $this->createWorkflow([
            'type' => WorkflowType::PERIODIC->value,
            'status' => WorkflowStatus::PAUSED->value,
            'rule_chain' => $this->periodicRuleChain('08:30'),
            'cron' => ['runTime' => 'day', 'executeTime' => '08:30', 'dayInterval' => 1],
        ]);

        $response = $this->postJson('/workflow/update', [
            'id' => $workflow->id,
            'rule_chain' => $this->triggerRuleChain(),
            'type' => WorkflowType::PERIODIC->value,
        ]);

        $response->assertOk();
        $response->assertJsonPath('code', 400);
        $response->assertJsonPath('msg', '周期型工作流缺少开始节点配置');
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createWorkflow(array $overrides = []): Workflow
    {
        return Workflow::query()->create(array_merge([
            'name' => 'Workflow Schedule Sync',
            'description' => 'workflow schedule sync test',
            'category_id' => 1,
            'create_user_id' => 1,
            'active' => false,
            'all_customer' => false,
            'type' => WorkflowType::TRIGGER->value,
            'status' => WorkflowStatus::PAUSED->value,
            'cron' => null,
            'version' => '1.0.0',
            'rule_chain' => $this->triggerRuleChain(),
        ], $overrides));
    }

    /**
     * @return array<string, mixed>
     */
    private function triggerRuleChain(): array
    {
        return [
            'nodes' => [
                ['id' => 'start-trigger', 'type' => 'start_trigger'],
                ['id' => 'end', 'type' => 'end'],
            ],
            'connections' => [
                ['source' => 'start-trigger', 'target' => 'end'],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function periodicRuleChain(string $executeTime): array
    {
        return [
            'nodes' => [
                [
                    'id' => 'start-periodic',
                    'type' => 'start_periodic',
                    'parameters' => [
                        'runTime' => 'day',
                        'dayInterval' => 1,
                        'executeTime' => $executeTime,
                    ],
                ],
                ['id' => 'end', 'type' => 'end'],
            ],
            'connections' => [
                ['source' => 'start-periodic', 'target' => 'end'],
            ],
        ];
    }

    private function createWorkflowTables(): void
    {
        Schema::create('admin_parameters', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('value')->nullable();
            $table->string('type')->nullable();
            $table->timestamps();
        });

        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('email')->nullable();
            $table->string('password')->nullable();
            $table->string('name')->nullable();
            $table->timestamps();
        });

        Schema::create('workflow_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->integer('sort')->default(0);
            $table->timestamps();
        });

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
            $table->json('cron')->nullable();
            $table->timestamp('last_run_at')->nullable();
            $table->timestamp('next_run_at')->nullable();
            $table->string('version')->default('1.0.0');
            $table->json('rule_chain')->nullable();
            $table->timestamps();
            $table->softDeletes();
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

        Schema::create('workflow_versions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('workflow_id');
            $table->unsignedInteger('version_no');
            $table->string('source')->default('save');
            $table->unsignedBigInteger('create_user_id')->nullable();
            $table->json('snapshot');
            $table->timestamps();
        });
    }
}
