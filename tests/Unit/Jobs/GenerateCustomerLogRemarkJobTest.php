<?php

namespace Tests\Unit\Jobs;

use App\Jobs\GenerateCustomerLogRemarkJob;
use App\Models\Customer;
use App\Models\CustomerLog;
use App\Models\Reservation;
use App\Services\CustomerLogRemark\CustomerLogRemarkRenderer;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Tests\TestCase;

class GenerateCustomerLogRemarkJobTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('customer_log', function (Blueprint $table) {
            $table->id();
            $table->uuid('customer_id')->nullable();
            $table->string('action')->nullable();
            $table->unsignedBigInteger('user_id')->default(0);
            $table->uuid('logable_id')->nullable();
            $table->string('logable_type')->nullable();
            $table->text('original')->nullable();
            $table->text('dirty')->nullable();
            $table->longText('remark')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('customer_log');

        parent::tearDown();
    }

    public function test_it_updates_only_logs_missing_remark_in_default_mode(): void
    {
        CustomerLog::query()->create([
            'id' => 1,
            'remark' => null,
            'logable_type' => Customer::class,
            'original' => ['name' => '张三'],
            'dirty' => ['name' => '李四'],
        ]);

        CustomerLog::query()->create([
            'id' => 2,
            'remark' => '人工备注',
            'logable_type' => Customer::class,
            'original' => ['name' => '王五'],
            'dirty' => ['name' => '赵六'],
        ]);

        $job = new GenerateCustomerLogRemarkJob([1, 2], false);
        $job->handle(app(CustomerLogRemarkRenderer::class));

        $this->assertSame('顾客姓名 由张三变更为李四', CustomerLog::query()->find(1)->remark);
        $this->assertSame('人工备注', CustomerLog::query()->find(2)->remark);
    }

    public function test_it_can_override_existing_remarks_in_force_mode(): void
    {
        CustomerLog::query()->create([
            'id' => 3,
            'remark' => '旧备注',
            'logable_type' => Reservation::class,
            'original' => ['status' => 1],
            'dirty' => ['status' => 2],
        ]);

        $job = new GenerateCustomerLogRemarkJob([3], true);
        $job->handle(app(CustomerLogRemarkRenderer::class));

        $this->assertSame('预约状态 由未上门变更为已到院', CustomerLog::query()->find(3)->remark);
    }

    public function test_it_continues_processing_when_one_log_render_fails(): void
    {
        CustomerLog::query()->create([
            'id' => 4,
            'remark' => null,
            'logable_type' => Customer::class,
            'original' => ['name' => '异常顾客'],
            'dirty' => ['name' => '异常顾客2'],
        ]);

        CustomerLog::query()->create([
            'id' => 5,
            'remark' => null,
            'logable_type' => Customer::class,
            'original' => ['name' => '王五'],
            'dirty' => ['name' => '赵六'],
        ]);

        $renderer = Mockery::mock(CustomerLogRemarkRenderer::class);
        $renderer->shouldReceive('render')
            ->twice()
            ->andReturnUsing(function (CustomerLog $log) {
                if ($log->id === 4) {
                    throw new \RuntimeException('broken payload');
                }

                return '顾客姓名 由王五变更为赵六';
            });

        $job = new GenerateCustomerLogRemarkJob([4, 5], false);
        $job->handle($renderer);

        $this->assertNull(CustomerLog::query()->find(4)->remark);
        $this->assertSame('顾客姓名 由王五变更为赵六', CustomerLog::query()->find(5)->remark);
    }

    public function test_it_does_not_update_remark_when_original_and_dirty_are_both_empty(): void
    {
        CustomerLog::query()->create([
            'id' => 6,
            'remark' => null,
            'logable_type' => Customer::class,
            'original' => [],
            'dirty' => [],
        ]);

        $job = new GenerateCustomerLogRemarkJob([6], false);
        $job->handle(app(CustomerLogRemarkRenderer::class));

        $this->assertNull(CustomerLog::query()->find(6)->remark);
    }

    public function test_it_does_not_update_remark_when_original_and_dirty_are_both_null(): void
    {
        CustomerLog::query()->create([
            'id' => 7,
            'remark' => null,
            'logable_type' => Customer::class,
            'original' => null,
            'dirty' => null,
        ]);

        $job = new GenerateCustomerLogRemarkJob([7], false);
        $job->handle(app(CustomerLogRemarkRenderer::class));

        $this->assertNull(CustomerLog::query()->find(7)->remark);
    }
}
