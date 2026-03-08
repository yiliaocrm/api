<?php

namespace Tests\Unit\Services\Workflow;

use App\Services\Workflow\WorkflowExecutionCleanupService;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class WorkflowExecutionCleanupServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-02-24 10:00:00');
        $this->createWorkflowExecutionTables();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        Schema::dropIfExists('workflow_execution_steps');
        Schema::dropIfExists('workflow_executions');
        parent::tearDown();
    }

    public function test_cleanup_deletes_only_expired_terminal_executions_and_steps(): void
    {
        $this->insertExecution(['id' => 1, 'status' => 'success', 'finished_at' => '2026-02-01 00:00:00']);
        $this->insertExecution(['id' => 2, 'status' => 'error', 'finished_at' => '2026-02-05 00:00:00']);
        $this->insertExecution(['id' => 3, 'status' => 'canceled', 'finished_at' => '2026-02-20 00:00:00']);
        $this->insertExecution(['id' => 4, 'status' => 'running', 'finished_at' => '2026-01-20 00:00:00']);
        $this->insertExecution(['id' => 5, 'status' => 'waiting', 'finished_at' => '2026-01-20 00:00:00']);
        $this->insertExecution([
            'id' => 6,
            'status' => 'success',
            'finished_at' => null,
            'created_at' => '2026-02-01 00:00:00',
            'updated_at' => '2026-02-01 00:00:00',
        ]);

        $this->insertStep(['workflow_execution_id' => 1]);
        $this->insertStep(['workflow_execution_id' => 1]);
        $this->insertStep(['workflow_execution_id' => 2]);
        $this->insertStep(['workflow_execution_id' => 3]);
        $this->insertStep(['workflow_execution_id' => 4]);
        $this->insertStep(['workflow_execution_id' => 6]);

        $service = app(WorkflowExecutionCleanupService::class);
        $stats = $service->cleanupExpiredExecutions(now()->subDays(14), 500, false);

        $this->assertSame(3, $stats['matched_executions']);
        $this->assertSame(4, $stats['deleted_steps']);
        $this->assertSame(3, $stats['deleted_executions']);
        $this->assertSame(1, $stats['batches']);

        $remainingExecutionIds = DB::table('workflow_executions')->orderBy('id')->pluck('id')->all();
        $remainingStepExecutionIds = DB::table('workflow_execution_steps')->orderBy('id')->pluck('workflow_execution_id')->all();

        $this->assertSame([3, 4, 5], $remainingExecutionIds);
        $this->assertSame([3, 4], $remainingStepExecutionIds);
    }

    public function test_cleanup_supports_dry_run_without_deleting_data(): void
    {
        $this->insertExecution(['id' => 11, 'status' => 'success', 'finished_at' => '2026-02-01 00:00:00']);
        $this->insertStep(['workflow_execution_id' => 11]);

        $service = app(WorkflowExecutionCleanupService::class);
        $stats = $service->cleanupExpiredExecutions(now()->subDays(14), 500, true);

        $this->assertSame(1, $stats['matched_executions']);
        $this->assertSame(0, $stats['deleted_steps']);
        $this->assertSame(0, $stats['deleted_executions']);
        $this->assertSame(0, $stats['batches']);
        $this->assertSame(1, DB::table('workflow_executions')->count());
        $this->assertSame(1, DB::table('workflow_execution_steps')->count());
    }

    public function test_cleanup_deletes_in_multiple_batches(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            $this->insertExecution([
                'id' => 100 + $i,
                'status' => 'success',
                'finished_at' => '2026-02-01 00:00:00',
            ]);
            $this->insertStep(['workflow_execution_id' => 100 + $i]);
        }

        $service = app(WorkflowExecutionCleanupService::class);
        $stats = $service->cleanupExpiredExecutions(now()->subDays(14), 2, false);

        $this->assertSame(5, $stats['matched_executions']);
        $this->assertSame(5, $stats['deleted_steps']);
        $this->assertSame(5, $stats['deleted_executions']);
        $this->assertSame(3, $stats['batches']);
        $this->assertSame(0, DB::table('workflow_executions')->count());
        $this->assertSame(0, DB::table('workflow_execution_steps')->count());
    }

    private function createWorkflowExecutionTables(): void
    {
        Schema::create('workflow_executions', function (Blueprint $table) {
            $table->id();
            $table->string('status')->default('running');
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
        });

        Schema::create('workflow_execution_steps', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('workflow_execution_id');
            $table->timestamps();
        });
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function insertExecution(array $overrides = []): void
    {
        DB::table('workflow_executions')->insert(array_merge([
            'id' => null,
            'status' => 'running',
            'finished_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ], $overrides));
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function insertStep(array $overrides = []): void
    {
        DB::table('workflow_execution_steps')->insert(array_merge([
            'workflow_execution_id' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ], $overrides));
    }
}

