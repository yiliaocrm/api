<?php

declare(strict_types=1);

namespace App\Services\Admin;

use Illuminate\Bus\BatchRepository;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Laravel\Horizon\Contracts\JobRepository;
use Laravel\Horizon\Contracts\MasterSupervisorRepository;
use Laravel\Horizon\Contracts\MetricsRepository;
use Laravel\Horizon\Contracts\SupervisorRepository;
use Laravel\Horizon\Contracts\TagRepository;
use Laravel\Horizon\Contracts\WorkloadRepository;
use Laravel\Horizon\Jobs\MonitorTag;
use Laravel\Horizon\Jobs\RetryFailedJob;
use Laravel\Horizon\Jobs\StopMonitoringTag;
use Laravel\Horizon\ProvisioningPlan;
use Laravel\Horizon\WaitTimeCalculator;

class HorizonService
{
    public function __construct(
        protected JobRepository $jobs,
        protected TagRepository $tags,
        protected MetricsRepository $metrics,
        protected WorkloadRepository $workload,
        protected MasterSupervisorRepository $masters,
        protected SupervisorRepository $supervisors,
        protected BatchRepository $batches,
    ) {}

    public function stats(): array
    {
        return [
            'failedJobs' => $this->jobs->countRecentlyFailed(),
            'jobsPerMinute' => $this->metrics->jobsProcessedPerMinute(),
            'pausedMasters' => $this->totalPausedMasters(),
            'periods' => [
                'failedJobs' => config('horizon.trim.recent_failed', config('horizon.trim.failed')),
                'recentJobs' => config('horizon.trim.recent'),
            ],
            'processes' => $this->totalProcessCount(),
            'queueWithMaxRuntime' => $this->metrics->queueWithMaximumRuntime(),
            'queueWithMaxThroughput' => $this->metrics->queueWithMaximumThroughput(),
            'recentJobs' => $this->jobs->countRecent(),
            'status' => $this->currentStatus(),
            'wait' => collect(app(WaitTimeCalculator::class)->calculate())->take(1),
        ];
    }

    public function workload(): array
    {
        return collect($this->workload->get())
            ->sortBy('name')
            ->values()
            ->toArray();
    }

    public function masters(): Collection
    {
        $masters = collect($this->masters->all())->keyBy('name')->sortBy('name');
        $supervisors = collect($this->supervisors->all())->sortBy('name')->groupBy('master');

        return $masters->each(function (object $master, string $name) use ($supervisors): void {
            $master->supervisors = ($supervisors->get($name) ?? collect())
                ->merge(
                    collect(ProvisioningPlan::get($name)->plan[$master->environment ?? config('horizon.env') ?? config('app.env')] ?? [])
                        ->map(function (mixed $value, string $key) use ($name): object {
                            $value = is_array($value) ? $value : [];

                            return (object) [
                                'name' => $name.':'.$key,
                                'master' => $name,
                                'status' => 'inactive',
                                'processes' => [],
                                'options' => [
                                    'queue' => array_key_exists('queue', $value) && is_array($value['queue'])
                                        ? implode(',', $value['queue'])
                                        : ($value['queue'] ?? ''),
                                    'balance' => $value['balance'] ?? null,
                                ],
                            ];
                        })
                )
                ->unique('name')
                ->values();
        });
    }

    public function jobMetrics(): array
    {
        return $this->metrics->measuredJobs();
    }

    public function jobMetricDetail(string $id): Collection
    {
        return collect($this->metrics->snapshotsForJob($id))
            ->map(function (object $record): object {
                $record->runtime = round($record->runtime / 1000, 3);
                $record->throughput = (int) $record->throughput;

                return $record;
            });
    }

    public function queueMetrics(): array
    {
        return $this->metrics->measuredQueues();
    }

    public function queueMetricDetail(string $id): Collection
    {
        return collect($this->metrics->snapshotsForQueue($id))
            ->map(function (object $record): object {
                $record->runtime = round($record->runtime / 1000, 3);
                $record->throughput = (int) $record->throughput;

                return $record;
            });
    }

    public function pendingJobs(int $startingAt = -1): array
    {
        $jobs = $this->jobs
            ->getPending($startingAt)
            ->map(fn (object $job): object => $this->decodeJobPayload($job))
            ->values();

        return [
            'jobs' => $jobs,
            'total' => $this->jobs->countPending(),
        ];
    }

    public function completedJobs(int $startingAt = -1): array
    {
        $jobs = $this->jobs
            ->getCompleted($startingAt)
            ->map(fn (object $job): object => $this->decodeJobPayload($job))
            ->values();

        return [
            'jobs' => $jobs,
            'total' => $this->jobs->countCompleted(),
        ];
    }

    public function failedJobs(int $startingAt = -1, ?string $tag = null): array
    {
        $jobs = $tag
            ? $this->paginateFailedByTag($startingAt, $tag)
            : $this->jobs->getFailed($startingAt)->map(fn (object $job): object => $this->decodeFailedJob($job));

        return [
            'jobs' => $jobs,
            'total' => $tag ? $this->tags->count('failed:'.$tag) : $this->jobs->countFailed(),
        ];
    }

    public function failedJobDetail(string $id): array
    {
        return (array) $this->jobs
            ->getJobs([$id])
            ->map(fn (object $job): object => $this->decodeFailedJob($job))
            ->first();
    }

    public function retryJob(string $id): array
    {
        dispatch(new RetryFailedJob($id));

        return [
            'message' => 'retry job dispatched',
            'id' => $id,
        ];
    }

    public function silencedJobs(int $startingAt = -1): array
    {
        $jobs = $this->jobs
            ->getSilenced($startingAt)
            ->map(fn (object $job): object => $this->decodeJobPayload($job))
            ->values();

        return [
            'jobs' => $jobs,
            'total' => $this->jobs->countSilenced(),
        ];
    }

    public function jobDetail(string $id): array
    {
        return (array) $this->jobs
            ->getJobs([$id])
            ->map(fn (object $job): object => $this->decodeJobPayload($job))
            ->first();
    }

    public function monitoring(): Collection
    {
        return collect($this->tags->monitoring())
            ->map(fn (string $tag): array => [
                'tag' => $tag,
                'count' => $this->tags->count($tag) + $this->tags->count('failed:'.$tag),
            ])
            ->sortBy('tag')
            ->values();
    }

    public function storeMonitoring(string $tag): array
    {
        dispatch(new MonitorTag($tag));

        return [
            'message' => 'monitor tag dispatched',
            'tag' => $tag,
        ];
    }

    public function monitoringJobs(string $tag, int $startingAt = 0, int $limit = 25): array
    {
        $jobIds = $this->tags->paginate($tag, $startingAt, $limit);

        return [
            'jobs' => $this->jobs
                ->getJobs($jobIds, $startingAt)
                ->map(fn (object $job): object => $this->decodeJobPayload($job))
                ->values(),
            'total' => $this->tags->count($tag),
        ];
    }

    public function destroyMonitoring(string $tag): array
    {
        dispatch(new StopMonitoringTag($tag));

        return [
            'message' => 'stop monitor tag dispatched',
            'tag' => $tag,
        ];
    }

    public function batches(?string $query = null, ?string $beforeId = null): array
    {
        try {
            $batches = $query
                ? $this->searchBatches($query, $beforeId)
                : $this->batches->get(50, $beforeId);
        } catch (QueryException) {
            $batches = [];
        }

        return [
            'batches' => $batches,
        ];
    }

    public function batchDetail(string $id): array
    {
        $batch = $this->batches->find($id);
        $failedJobs = null;

        if ($batch) {
            $failedJobs = $this->jobs->getJobs($batch->failedJobIds);
        }

        return [
            'batch' => $batch,
            'failedJobs' => $failedJobs,
        ];
    }

    public function retryBatch(string $id): array
    {
        $batch = $this->batches->find($id);

        if ($batch) {
            $this->jobs
                ->getJobs($batch->failedJobIds)
                ->reject(function (object $job): bool {
                    $payload = json_decode((string) $job->payload);

                    return isset($payload->retry_of);
                })
                ->each(function (object $job): void {
                    dispatch(new RetryFailedJob($job->id));
                });
        }

        return [
            'message' => 'retry batch dispatched',
            'id' => $id,
        ];
    }

    public function currentStatus(): string
    {
        $masters = $this->masters->all();

        if ($masters === []) {
            return 'inactive';
        }

        return collect($masters)->every(static fn (object $master): bool => $master->status === 'paused')
            ? 'paused'
            : 'running';
    }

    protected function totalProcessCount(): int
    {
        return (int) collect($this->supervisors->all())
            ->reduce(
                static fn (int $carry, object $supervisor): int => $carry + (int) collect($supervisor->processes)->sum(),
                0
            );
    }

    protected function totalPausedMasters(): int
    {
        $masters = $this->masters->all();

        if ($masters === []) {
            return 0;
        }

        return collect($masters)
            ->filter(static fn (object $master): bool => $master->status === 'paused')
            ->count();
    }

    protected function paginateFailedByTag(int $startingAt, string $tag): Collection
    {
        $jobIds = $this->tags->paginate(
            'failed:'.$tag,
            $startingAt + 1,
            50
        );

        return $this->jobs
            ->getJobs($jobIds, $startingAt)
            ->map(fn (object $job): object => $this->decodeFailedJob($job));
    }

    protected function searchBatches(string $query, ?string $beforeId = null): array
    {
        $query = str_replace(['%', '_'], ['\\%', '\\_'], $query);

        return DB::connection(config('queue.batching.database'))
            ->table(config('queue.batching.table', 'job_batches'))
            ->where(function ($builder) use ($query): void {
                $builder->where('name', 'like', "%{$query}%")
                    ->orWhere('id', 'like', "%{$query}%");
            })
            ->orderByDesc('id')
            ->limit(50)
            ->when($beforeId, static fn ($builder, $id) => $builder->where('id', '<', $id))
            ->pluck('id')
            ->map(fn (string $batchId) => $this->batches->find($batchId))
            ->filter()
            ->values()
            ->all();
    }

    protected function decodeJobPayload(object $job): object
    {
        $job->payload = json_decode((string) $job->payload);

        return $job;
    }

    protected function decodeFailedJob(object $job): object
    {
        $job->payload = json_decode((string) $job->payload);
        $job->exception = mb_convert_encoding((string) $job->exception, 'UTF-8');
        $job->context = json_decode((string) ($job->context ?? ''));

        $job->retried_by = collect(! is_null($job->retried_by) ? json_decode((string) $job->retried_by) : [])
            ->sortByDesc('retried_at')
            ->values();

        return $job;
    }
}
