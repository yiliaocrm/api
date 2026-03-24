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

    public const string SKIPPED_REMARK = '系统未生成备注';

    public const string FAILED_REMARK = '系统生成备注失败';

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
                        $this->updateRemark($log->id, self::SKIPPED_REMARK);

                        return;
                    }

                    $this->updateRemark($log->id, $remark);
                } catch (\Throwable $exception) {
                    $this->updateRemark($log->id, self::FAILED_REMARK);

                    logger()->warning('customer_log_remark_job_failed', [
                        'log_id' => $log->id,
                        'error' => $exception->getMessage(),
                    ]);
                }
            });
    }

    private function updateRemark(int|string $logId, string $remark): void
    {
        $query = CustomerLog::query()->whereKey($logId);

        if (! $this->force) {
            $query->where(function ($builder) {
                $builder->whereNull('remark')->orWhere('remark', '');
            });
        }

        $query->update(['remark' => $remark]);
    }
}
