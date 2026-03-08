<?php

namespace Tests\Feature\Workflow;

use App\Enums\WorkflowStatus;
use App\Enums\WorkflowType;
use App\Http\Requests\Web\WorkflowRequest;
use App\Models\Workflow;
use App\Models\WorkflowVersion;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class WorkflowPublishSaveTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->createWorkflowTables();
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('admin_parameters');
        Schema::dropIfExists('workflow_versions');
        Schema::dropIfExists('workflow_customer_groups');
        Schema::dropIfExists('customer_groups');
        Schema::dropIfExists('workflows');

        parent::tearDown();
    }

    public function test_publish_source_snapshot_is_created_after_initial_save(): void
    {
        $workflow = $this->createWorkflow('Publish Save Workflow');

        $request = new WorkflowRequest;
        $request->createVersionSnapshot($workflow, 'publish');

        $version = WorkflowVersion::query()->first();

        $this->assertNotNull($version);
        $this->assertSame(1, $version->version_no);
        $this->assertSame('publish', $version->source);
        $this->assertSame(WorkflowType::TRIGGER->value, $version->snapshot['type']);
        $this->assertSame(WorkflowStatus::PAUSED->value, $version->snapshot['status']);
    }

    public function test_publish_source_snapshot_increments_version_after_update(): void
    {
        $workflow = $this->createWorkflow('Publish Save Workflow');

        $request = new WorkflowRequest;
        $request->createVersionSnapshot($workflow, 'publish');

        $workflow->update([
            'name' => 'Publish Save Workflow Updated',
            'description' => 'updated description',
        ]);

        $request->createVersionSnapshot($workflow->fresh(), 'publish');

        $this->assertSame(2, WorkflowVersion::query()->count());

        $latestVersion = WorkflowVersion::query()->orderByDesc('id')->first();
        $this->assertNotNull($latestVersion);
        $this->assertSame(2, $latestVersion->version_no);
        $this->assertSame('publish', $latestVersion->source);
        $this->assertSame(WorkflowType::TRIGGER->value, $latestVersion->snapshot['type']);
        $this->assertSame(WorkflowStatus::PAUSED->value, $latestVersion->snapshot['status']);
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

    private function createWorkflow(string $name): Workflow
    {
        return Workflow::query()->create([
            'name' => $name,
            'description' => 'workflow publish save integration test',
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
        ]);
    }
}
