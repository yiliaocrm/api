<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\Admin\HorizonService;
use Illuminate\Bus\BatchRepository;
use Illuminate\Support\Facades\Bus;
use Laravel\Horizon\Contracts\JobRepository;
use Laravel\Horizon\Contracts\MasterSupervisorRepository;
use Laravel\Horizon\Contracts\MetricsRepository;
use Laravel\Horizon\Contracts\SupervisorRepository;
use Laravel\Horizon\Contracts\TagRepository;
use Laravel\Horizon\Contracts\WorkloadRepository;
use Laravel\Horizon\Jobs\RetryFailedJob;
use Tests\TestCase;

class HorizonServiceTest extends TestCase
{
    protected JobRepository $jobs;

    protected TagRepository $tags;

    protected MetricsRepository $metrics;

    protected WorkloadRepository $workload;

    protected MasterSupervisorRepository $masters;

    protected SupervisorRepository $supervisors;

    protected BatchRepository $batches;

    protected HorizonService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->jobs = $this->createMock(JobRepository::class);
        $this->tags = $this->createMock(TagRepository::class);
        $this->metrics = $this->createMock(MetricsRepository::class);
        $this->workload = $this->createMock(WorkloadRepository::class);
        $this->masters = $this->createMock(MasterSupervisorRepository::class);
        $this->supervisors = $this->createMock(SupervisorRepository::class);
        $this->batches = $this->createMock(BatchRepository::class);

        $this->service = new HorizonService(
            $this->jobs,
            $this->tags,
            $this->metrics,
            $this->workload,
            $this->masters,
            $this->supervisors,
            $this->batches,
        );
    }

    public function test_stats_returns_correct_structure(): void
    {
        $this->jobs->method('countRecentlyFailed')->willReturn(5);
        $this->jobs->method('countRecent')->willReturn(100);
        $this->metrics->method('jobsProcessedPerMinute')->willReturn(10.5);
        $this->metrics->method('queueWithMaximumRuntime')->willReturn('default');
        $this->metrics->method('queueWithMaximumThroughput')->willReturn('default');
        $this->masters->method('all')->willReturn([]);
        $this->supervisors->method('all')->willReturn([]);

        $stats = $this->service->stats();

        $this->assertArrayHasKey('failedJobs', $stats);
        $this->assertArrayHasKey('jobsPerMinute', $stats);
        $this->assertArrayHasKey('pausedMasters', $stats);
        $this->assertArrayHasKey('periods', $stats);
        $this->assertArrayHasKey('processes', $stats);
        $this->assertArrayHasKey('recentJobs', $stats);
        $this->assertArrayHasKey('status', $stats);
        $this->assertEquals(5, $stats['failedJobs']);
        $this->assertEquals(100, $stats['recentJobs']);
        $this->assertEquals(10.5, $stats['jobsPerMinute']);
    }

    public function test_pending_jobs_returns_paginated_structure(): void
    {
        $this->jobs->method('getPending')->willReturn(collect([]));
        $this->jobs->method('countPending')->willReturn(0);

        $result = $this->service->pendingJobs();

        $this->assertArrayHasKey('jobs', $result);
        $this->assertArrayHasKey('total', $result);
        $this->assertEquals(0, $result['total']);
    }

    public function test_completed_jobs_returns_paginated_structure(): void
    {
        $this->jobs->method('getCompleted')->willReturn(collect([]));
        $this->jobs->method('countCompleted')->willReturn(0);

        $result = $this->service->completedJobs();

        $this->assertArrayHasKey('jobs', $result);
        $this->assertArrayHasKey('total', $result);
        $this->assertEquals(0, $result['total']);
    }

    public function test_failed_jobs_returns_paginated_structure(): void
    {
        $this->jobs->method('getFailed')->willReturn(collect([]));
        $this->jobs->method('countFailed')->willReturn(0);

        $result = $this->service->failedJobs();

        $this->assertArrayHasKey('jobs', $result);
        $this->assertArrayHasKey('total', $result);
        $this->assertEquals(0, $result['total']);
    }

    public function test_retry_job_dispatches_retry_failed_job(): void
    {
        Bus::fake();

        $result = $this->service->retryJob('test-id');

        Bus::assertDispatched(RetryFailedJob::class);
        $this->assertArrayHasKey('message', $result);
        $this->assertArrayHasKey('id', $result);
        $this->assertEquals('test-id', $result['id']);
    }

    public function test_current_status_returns_inactive_when_no_masters(): void
    {
        $this->masters->method('all')->willReturn([]);

        $status = $this->service->currentStatus();

        $this->assertEquals('inactive', $status);
    }

    public function test_current_status_returns_paused_when_all_paused(): void
    {
        $this->masters->method('all')->willReturn([
            (object) ['status' => 'paused', 'name' => 'master-1'],
            (object) ['status' => 'paused', 'name' => 'master-2'],
        ]);

        $status = $this->service->currentStatus();

        $this->assertEquals('paused', $status);
    }

    public function test_current_status_returns_running_when_any_running(): void
    {
        $this->masters->method('all')->willReturn([
            (object) ['status' => 'running', 'name' => 'master-1'],
            (object) ['status' => 'paused', 'name' => 'master-2'],
        ]);

        $status = $this->service->currentStatus();

        $this->assertEquals('running', $status);
    }

    public function test_batches_returns_structure(): void
    {
        $this->batches->method('get')->willReturn([]);

        $result = $this->service->batches();

        $this->assertArrayHasKey('batches', $result);
    }

    public function test_silenced_jobs_returns_paginated_structure(): void
    {
        $this->jobs->method('getSilenced')->willReturn(collect([]));
        $this->jobs->method('countSilenced')->willReturn(0);

        $result = $this->service->silencedJobs();

        $this->assertArrayHasKey('jobs', $result);
        $this->assertArrayHasKey('total', $result);
    }
}
