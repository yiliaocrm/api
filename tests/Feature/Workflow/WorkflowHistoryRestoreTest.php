<?php

namespace Tests\Feature\Workflow;

use App\Enums\WorkflowStatus;
use App\Enums\WorkflowType;
use App\Models\Workflow;
use App\Models\WorkflowVersion;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class WorkflowHistoryRestoreTest extends TestCase
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

    public function test_history_restore_updates_workflow_and_creates_restore_snapshot(): void
    {
        $this->seedWorkflowBaseData();
        $workflow = $this->createWorkflow([
            'name' => 'Current Workflow',
            'description' => 'current description',
            'category_id' => 1,
            'all_customer' => true,
            'type' => WorkflowType::TRIGGER->value,
            'status' => WorkflowStatus::ACTIVE->value,
            'rule_chain' => [
                'nodes' => [
                    ['id' => 'start-current', 'type' => 'start_trigger'],
                    ['id' => 'end-current', 'type' => 'end'],
                ],
                'connections' => [
                    ['source' => 'start-current', 'target' => 'end-current'],
                ],
            ],
        ]);

        $version = WorkflowVersion::query()->create([
            'workflow_id' => $workflow->id,
            'version_no' => 1,
            'source' => 'save',
            'create_user_id' => 1,
            'snapshot' => [
                'workflow_id' => $workflow->id,
                'name' => 'Restored Workflow',
                'description' => 'restored description',
                'category_id' => 2,
                'type' => WorkflowType::TRIGGER->value,
                'all_customer' => false,
                'customer_group_ids' => [101, 102],
                'cron' => null,
                'rule_chain' => [
                    'nodes' => [
                        ['id' => 'start-restored', 'type' => 'start_trigger'],
                        ['id' => 'end-restored', 'type' => 'end'],
                    ],
                    'connections' => [
                        ['source' => 'start-restored', 'target' => 'end-restored'],
                    ],
                ],
                'status' => WorkflowStatus::PAUSED->value,
                'saved_at' => now()->toDateTimeString(),
            ],
        ]);

        $response = $this->postJson('/workflow/history-restore', ['id' => $version->id]);

        $response->assertOk();
        $response->assertJsonPath('code', 200);
        $response->assertJsonPath('data.restored_from_version_id', $version->id);

        $workflow->refresh();

        $this->assertSame('Restored Workflow', $workflow->name);
        $this->assertSame('restored description', $workflow->description);
        $this->assertSame(2, $workflow->category_id);
        $this->assertFalse((bool) $workflow->all_customer);
        $this->assertSame(WorkflowStatus::ACTIVE->value, $workflow->status->value);
        $this->assertSame('start-restored', $workflow->rule_chain['nodes'][0]['id']);

        $groupIds = DB::table('workflow_customer_groups')
            ->where('workflow_id', $workflow->id)
            ->orderBy('customer_group_id')
            ->pluck('customer_group_id')
            ->map(fn ($item) => (int) $item)
            ->values()
            ->all();

        $this->assertSame([101, 102], $groupIds);

        $this->assertSame(2, WorkflowVersion::query()->count());
        $latestVersion = WorkflowVersion::query()->orderByDesc('version_no')->first();
        $this->assertNotNull($latestVersion);
        $this->assertSame('restore', $latestVersion->source);
        $this->assertSame(2, $latestVersion->version_no);
    }

    public function test_history_restore_fails_when_snapshot_rule_chain_is_invalid(): void
    {
        $this->seedWorkflowBaseData();
        $workflow = $this->createWorkflow();

        $version = WorkflowVersion::query()->create([
            'workflow_id' => $workflow->id,
            'version_no' => 1,
            'source' => 'save',
            'create_user_id' => 1,
            'snapshot' => [
                'workflow_id' => $workflow->id,
                'name' => 'Invalid Snapshot',
                'rule_chain' => null,
            ],
        ]);

        $response = $this->postJson('/workflow/history-restore', ['id' => $version->id]);

        $response->assertOk();
        $response->assertJsonPath('code', 400);

        $this->assertSame(1, WorkflowVersion::query()->count());
    }

    private function seedWorkflowBaseData(): void
    {
        DB::table('users')->insert([
            'id' => 1,
            'name' => 'workflow-test-user',
            'email' => 'workflow-test@example.com',
            'password' => 'secret',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('workflow_categories')->insert([
            ['id' => 1, 'name' => 'Category 1', 'sort' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'name' => 'Category 2', 'sort' => 2, 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('customer_groups')->insert([
            ['id' => 101, 'name' => 'Group 101', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 102, 'name' => 'Group 102', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createWorkflow(array $overrides = []): Workflow
    {
        return Workflow::query()->create(array_merge([
            'name' => 'Workflow to Restore',
            'description' => 'restore test',
            'category_id' => 1,
            'create_user_id' => 1,
            'active' => false,
            'all_customer' => false,
            'type' => WorkflowType::TRIGGER->value,
            'status' => WorkflowStatus::PAUSED->value,
            'cron' => null,
            'version' => '1.0.0',
            'rule_chain' => [
                'nodes' => [
                    ['id' => 'start', 'type' => 'start_trigger'],
                    ['id' => 'end', 'type' => 'end'],
                ],
                'connections' => [
                    ['source' => 'start', 'target' => 'end'],
                ],
            ],
        ], $overrides));
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
            $table->string('cron')->nullable();
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
