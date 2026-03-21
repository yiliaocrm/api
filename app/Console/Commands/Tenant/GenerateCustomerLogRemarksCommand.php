<?php

namespace App\Console\Commands\Tenant;

use App\Jobs\GenerateCustomerLogRemarkJob;
use App\Models\Admin\Tenant;
use App\Models\CustomerLog;
use Illuminate\Console\Command;

class GenerateCustomerLogRemarksCommand extends Command
{
    protected $signature = 'app:generate-customer-log-remarks
        {--limit=500 : 每批扫描多少条日志}
        {--chunk=100 : 每个 Job 处理多少条日志}
        {--force : 是否重建已有 remark}';

    protected $description = '扫描顾客日志并异步生成 remark';

    public function handle(): int
    {
        $limit = max(1, (int) $this->option('limit'));
        $chunk = max(1, (int) $this->option('chunk'));
        $force = (bool) $this->option('force');

        Tenant::query()
            ->where('status', 'run')
            ->get()
            ->runForEach(function ($tenant) use ($limit, $chunk, $force) {
                $this->dispatchForTenant($tenant, $limit, $chunk, $force);
            });

        return self::SUCCESS;
    }

    private function dispatchForTenant(mixed $tenant, int $limit, int $chunk, bool $force): void
    {
        $query = CustomerLog::query()
            ->orderBy('id');

        if (! $force) {
            $query->where(function ($builder) {
                $builder->whereNull('remark')->orWhere('remark', '');
            });
        }

        $dispatched = 0;

        $query->chunkById($limit, function ($logs) use ($chunk, $force, &$dispatched) {
            $ids = $logs->pluck('id')->all();
            $dispatched += count($ids);

            foreach (array_chunk($ids, $chunk) as $batch) {
                dispatch(new GenerateCustomerLogRemarkJob($batch, $force));
            }
        }, 'id');

        $this->info(sprintf(
            'tenant=%s dispatched=%d force=%s',
            $tenant->id ?? 'unknown',
            $dispatched,
            $force ? 'true' : 'false'
        ));
    }
}
