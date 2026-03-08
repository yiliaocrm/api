<?php

namespace Tests\Unit\Services\Workflow\Executors;

use App\Models\Followup;
use App\Models\WorkflowExecution;
use App\Services\Workflow\Executors\CreateFollowupExecutor;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CreateFollowupExecutorTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->createFollowupTables();
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('customer_log');
        Schema::dropIfExists('followup');
        Schema::dropIfExists('customer');

        parent::tearDown();
    }

    public function test_execute_creates_followup_with_relative_date(): void
    {
        \Illuminate\Support\Facades\DB::table('customer')->insert(['id' => 'customer-001', 'name' => 'Test']);

        $executor = new CreateFollowupExecutor;
        $execution = new WorkflowExecution([
            'trigger_user_id' => 88,
            'context_data' => [
                'payload' => ['id' => 'customer-001'],
                'trigger' => ['model_type' => 'customer', 'model_id' => 'customer-001'],
            ],
        ]);

        $result = $executor->execute($execution, [
            'configuration' => [
                'title' => '术后回访',
                'type' => 1,
                'tool' => 2,
                'followup_user' => 1001,
                'date_mode' => 'relative',
                'date_offset' => 1,
                'date_unit' => 'days',
            ],
        ]);

        $this->assertTrue((bool) ($result['created'] ?? false));
        $this->assertNotEmpty($result['followup_id'] ?? null);
        $this->assertSame('customer-001', $result['customer_id'] ?? null);

        $followup = Followup::query()->find($result['followup_id']);
        $this->assertNotNull($followup);
        $this->assertSame(88, $followup->user_id);
    }

    public function test_execute_returns_error_when_customer_id_missing(): void
    {
        $executor = new CreateFollowupExecutor;
        $execution = new WorkflowExecution([
            'context_data' => [
                'payload' => [],
                'trigger' => ['model_type' => 'reservation', 'model_id' => 'R-1'],
            ],
        ]);

        $result = $executor->execute($execution, [
            'configuration' => [
                'title' => '术后回访',
                'type' => 1,
                'tool' => 2,
                'followup_user' => 1001,
                'date_mode' => 'relative',
                'date_offset' => 1,
                'date_unit' => 'days',
            ],
        ]);

        $this->assertFalse((bool) ($result['created'] ?? true));
        $this->assertSame('无法获取客户ID', $result['error'] ?? null);
        $this->assertSame(0, Followup::query()->count());
    }

    public function test_execute_creates_followup_immediately_when_relative_date_offset_is_zero(): void
    {
        \Illuminate\Support\Facades\DB::table('customer')->insert(['id' => 'customer-001', 'name' => 'Test']);

        $executor = new CreateFollowupExecutor;
        $execution = new WorkflowExecution([
            'trigger_user_id' => 88,
            'context_data' => [
                'payload' => ['id' => 'customer-001'],
                'trigger' => ['model_type' => 'customer', 'model_id' => 'customer-001'],
            ],
        ]);

        $result = $executor->execute($execution, [
            'configuration' => [
                'title' => '术后回访',
                'type' => 1,
                'tool' => 2,
                'followup_user' => 1001,
                'date_mode' => 'relative',
                'date_offset' => 0,
                'date_unit' => 'days',
            ],
        ]);

        $this->assertTrue((bool) ($result['created'] ?? false));
        $this->assertNotEmpty($result['followup_id'] ?? null);

        $followup = Followup::query()->find($result['followup_id']);
        $this->assertNotNull($followup);
        $this->assertSame(now()->toDateString(), $followup->date);
    }

    public function test_execute_uses_payload_customer_id_for_non_customer_model_trigger(): void
    {
        \Illuminate\Support\Facades\DB::table('customer')->insert(['id' => 'customer-uuid-001', 'name' => 'Test']);

        $executor = new CreateFollowupExecutor;
        $execution = new WorkflowExecution([
            'trigger_user_id' => 88,
            'context_data' => [
                'payload' => [
                    'id' => 'treatment-uuid-001',
                    'customer_id' => 'customer-uuid-001',
                ],
                'trigger' => [
                    'model_type' => 'treatment',
                    'model_id' => 'treatment-uuid-001',
                ],
            ],
        ]);

        $result = $executor->execute($execution, [
            'configuration' => [
                'title' => '术后回访',
                'type' => 1,
                'tool' => 2,
                'followup_user' => 1001,
                'date_mode' => 'relative',
                'date_offset' => 1,
                'date_unit' => 'days',
            ],
        ]);

        $this->assertTrue((bool) ($result['created'] ?? false));
        $this->assertNotEmpty($result['followup_id'] ?? null);
        $this->assertSame('customer-uuid-001', $result['customer_id'] ?? null);

        $followup = Followup::query()->find($result['followup_id']);
        $this->assertNotNull($followup);
        $this->assertSame('customer-uuid-001', $followup->customer_id);
    }

    public function test_execute_returns_error_when_customer_not_exists(): void
    {
        $executor = new CreateFollowupExecutor;
        $execution = new WorkflowExecution([
            'trigger_user_id' => 88,
            'context_data' => [
                'payload' => ['id' => 'non-existent-customer'],
                'trigger' => ['model_type' => 'customer', 'model_id' => 'non-existent-customer'],
            ],
        ]);

        $result = $executor->execute($execution, [
            'configuration' => [
                'title' => '术后回访',
                'type' => 1,
                'tool' => 2,
                'followup_user' => 1001,
                'date_mode' => 'relative',
                'date_offset' => 1,
                'date_unit' => 'days',
            ],
        ]);

        $this->assertFalse((bool) ($result['created'] ?? true));
        $this->assertStringContainsString('客户不存在', $result['error'] ?? '');
        $this->assertSame(0, Followup::query()->count());
    }

    public function test_execute_returns_error_when_non_customer_trigger_missing_customer_id(): void
    {
        $executor = new CreateFollowupExecutor;
        $execution = new WorkflowExecution([
            'trigger_user_id' => 88,
            'context_data' => [
                'payload' => [
                    'id' => 'treatment-uuid-001',
                ],
                'trigger' => [
                    'model_type' => 'treatment',
                    'model_id' => 'treatment-uuid-001',
                ],
            ],
        ]);

        $result = $executor->execute($execution, [
            'configuration' => [
                'title' => '术后回访',
                'type' => 1,
                'tool' => 2,
                'followup_user' => 1001,
                'date_mode' => 'relative',
                'date_offset' => 1,
                'date_unit' => 'days',
            ],
        ]);

        $this->assertFalse((bool) ($result['created'] ?? true));
        $this->assertSame('无法获取客户ID', $result['error'] ?? null);
        $this->assertSame(0, Followup::query()->count());
    }

    private function createFollowupTables(): void
    {
        Schema::create('followup', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('customer_id');
            $table->tinyInteger('type')->nullable();
            $table->tinyInteger('status')->default(1);
            $table->tinyInteger('tool')->nullable();
            $table->string('title');
            $table->date('date');
            $table->dateTime('time')->nullable();
            $table->text('remark')->nullable();
            $table->integer('followup_user');
            $table->integer('execute_user')->nullable();
            $table->integer('user_id');
            $table->timestamps();
        });

        Schema::create('customer', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name')->nullable();
            $table->integer('ascription')->nullable();
            $table->integer('consultant')->nullable();
            $table->integer('service_id')->nullable();
            $table->integer('doctor_id')->nullable();
            $table->timestamps();
        });

        Schema::create('customer_log', function (Blueprint $table) {
            $table->id();
            $table->string('customer_id')->nullable();
            $table->string('action')->nullable();
            $table->integer('user_id')->default(0);
            $table->text('original')->nullable();
            $table->text('dirty')->nullable();
            $table->string('logable_type')->nullable();
            $table->string('logable_id')->nullable();
            $table->timestamps();
        });
    }
}
