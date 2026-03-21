<?php

namespace App\Jobs;

use App\Models\CustomerLog;
use App\Services\CustomerLogRemark\CustomerLogRemarkRenderer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateCustomerLogRemarkJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public array $logIds,
        public bool $force = false,
    ) {}

    public function handle(CustomerLogRemarkRenderer $renderer): void
    {
        CustomerLog::query()
            ->whereIn('id', $this->logIds)
            ->orderBy('id')
            ->get()
            ->each(function (CustomerLog $log) use ($renderer) {
                try {
                    if (! $this->force && filled($log->remark)) {
                        return;
                    }

                    $remark = $renderer->render($log);
                    if ($remark === '') {
                        return;
                    }

                    $query = CustomerLog::query()->whereKey($log->id);

                    if (! $this->force) {
                        $query->where(function ($builder) {
                            $builder->whereNull('remark')->orWhere('remark', '');
                        });
                    }

                    $query->update(['remark' => $remark]);
                } catch (\Throwable $exception) {
                    logger()->warning('customer_log_remark_job_failed', [
                        'log_id' => $log->id,
                        'error' => $exception->getMessage(),
                    ]);
                }
            });
    }
}
