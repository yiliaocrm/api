<?php

namespace Tests\Unit\Console\Commands\Tenant;

use App\Console\Commands\Tenant\GenerateCustomerLogRemarksCommand;
use App\Jobs\GenerateCustomerLogRemarkJob;
use App\Models\CustomerLog;
use Illuminate\Console\OutputStyle;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Tests\TestCase;

class GenerateCustomerLogRemarksCommandTest extends TestCase
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

    public function test_it_dispatches_jobs_for_logs_without_remark(): void
    {
        Queue::fake();

        CustomerLog::query()->create([
            'id' => 1,
            'remark' => null,
            'dirty' => ['name' => '张三'],
        ]);
        CustomerLog::query()->create(['id' => 2, 'remark' => '已有']);
        CustomerLog::query()->create([
            'id' => 3,
            'remark' => '',
            'dirty' => ['name' => '李四'],
        ]);

        $this->invokeDispatch(limit: 10, chunk: 2, force: false);

        Queue::assertPushed(GenerateCustomerLogRemarkJob::class, 1);
        Queue::assertPushed(GenerateCustomerLogRemarkJob::class, function (GenerateCustomerLogRemarkJob $job) {
            return $job->logIds === [1, 3] && $job->force === false;
        });
    }

    public function test_it_includes_existing_remarks_in_force_mode(): void
    {
        Queue::fake();

        CustomerLog::query()->create(['id' => 1, 'remark' => '已有']);

        $this->invokeDispatch(limit: 10, chunk: 1, force: true);

        Queue::assertPushed(GenerateCustomerLogRemarkJob::class, function (GenerateCustomerLogRemarkJob $job) {
            return $job->logIds === [1] && $job->force === true;
        });
    }

    public function test_it_keeps_dispatching_until_all_pending_logs_are_queued(): void
    {
        Queue::fake();

        foreach (range(1, 5) as $id) {
            CustomerLog::query()->create([
                'id' => $id,
                'remark' => null,
                'dirty' => ['name' => "顾客{$id}"],
            ]);
        }

        $this->invokeDispatch(limit: 2, chunk: 1, force: false);

        Queue::assertPushed(GenerateCustomerLogRemarkJob::class, 5);

        $dispatchedIds = collect(Queue::pushed(GenerateCustomerLogRemarkJob::class))
            ->map(fn (GenerateCustomerLogRemarkJob $job) => $job->logIds[0])
            ->sort()
            ->values()
            ->all();

        $this->assertSame([1, 2, 3, 4, 5], $dispatchedIds);
    }

    public function test_it_skips_logs_without_any_renderable_payload(): void
    {
        Queue::fake();

        CustomerLog::query()->create([
            'id' => 10,
            'remark' => null,
            'original' => null,
            'dirty' => null,
        ]);

        CustomerLog::query()->create([
            'id' => 11,
            'remark' => null,
            'original' => [],
            'dirty' => [],
        ]);

        CustomerLog::query()->create([
            'id' => 12,
            'remark' => null,
            'original' => null,
            'dirty' => ['name' => '张三'],
        ]);

        $this->invokeDispatch(limit: 10, chunk: 10, force: false);

        Queue::assertPushed(GenerateCustomerLogRemarkJob::class, 1);
        Queue::assertPushed(GenerateCustomerLogRemarkJob::class, function (GenerateCustomerLogRemarkJob $job) {
            return $job->logIds === [12] && $job->force === false;
        });
    }

    public function test_it_still_dispatches_logs_that_only_have_original_payload(): void
    {
        Queue::fake();

        CustomerLog::query()->create([
            'id' => 20,
            'remark' => null,
            'original' => ['consultant' => 1],
            'dirty' => null,
        ]);

        $this->invokeDispatch(limit: 10, chunk: 10, force: false);

        Queue::assertPushed(GenerateCustomerLogRemarkJob::class, 1);
        Queue::assertPushed(GenerateCustomerLogRemarkJob::class, function (GenerateCustomerLogRemarkJob $job) {
            return $job->logIds === [20] && $job->force === false;
        });
    }

    private function invokeDispatch(int $limit, int $chunk, bool $force): void
    {
        $command = new GenerateCustomerLogRemarksCommand;
        $command->setLaravel($this->app);
        $command->setOutput(new OutputStyle(new ArrayInput([]), new NullOutput));

        $method = new \ReflectionMethod($command, 'dispatchForTenant');
        $method->setAccessible(true);
        $method->invoke($command, $this->fakeTenant(), $limit, $chunk, $force);
    }

    private function fakeTenant(): object
    {
        return new class
        {
            public string $id = 'test-tenant';
        };
    }
}
