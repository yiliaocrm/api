<?php

namespace App\Console\Commands\Tenant;

use Carbon\Carbon;
use App\Models\OperationLog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Stancl\Tenancy\Concerns\HasATenantsOption;
use Stancl\Tenancy\Concerns\TenantAwareCommand;
use Symfony\Component\Console\Command\Command as CommandAlias;

class CleanupOperationLogsCommand extends Command
{
    use TenantAwareCommand, HasATenantsOption;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:cleanup-operation-logs {--days=30 : 清理多少天前的操作日志}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '定期清理操作日志记录';

    /**
     * 清理统计信息
     *
     * @var array
     */
    protected array $stats = [
        'deleted_logs' => 0,
        'errors'       => 0,
    ];

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->specifyParameters();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->displayStartMessage();

        $cutoffDate = $this->calculateCutoffDate();
        $deletedCount = $this->deleteExpiredLogs($cutoffDate);

        if ($deletedCount === 0) {
            $this->displayNoLogsMessage();
            return CommandAlias::SUCCESS;
        }

        $this->displayResults();
        $this->logCleanupResults($cutoffDate);

        return $this->stats['errors'] > 0 ? CommandAlias::FAILURE : CommandAlias::SUCCESS;
    }

    /**
     * 显示开始消息
     */
    protected function displayStartMessage(): void
    {
        $days       = $this->getDays();
        $tenantName = $this->getTenantName();

        $this->info("开始为租户 [{$tenantName}] 清理 {$days} 天前的操作日志...");
    }

    /**
     * 计算清理截止时间
     */
    protected function calculateCutoffDate(): Carbon
    {
        $cutoffDate = Carbon::now()->subDays($this->getDays());
        $this->info("清理截止时间：{$cutoffDate->toDateTimeString()}");

        return $cutoffDate;
    }

    /**
     * 删除过期的操作日志
     */
    protected function deleteExpiredLogs(Carbon $cutoffDate): int
    {
        try {
            $deletedCount = OperationLog::query()
                ->where('created_at', '<', $cutoffDate)
                ->delete();

            $this->stats['deleted_logs'] = $deletedCount;

            if ($deletedCount > 0) {
                $this->info("✓ 成功删除 {$deletedCount} 条操作日志记录");
            }

            return $deletedCount;

        } catch (\Exception $e) {
            $this->handleError($e);
            return 0;
        }
    }

    /**
     * 显示没有找到日志的消息
     */
    protected function displayNoLogsMessage(): void
    {
        $tenantName = $this->getTenantName();
        $this->info("租户 [{$tenantName}] 没有找到需要清理的操作日志");
    }

    /**
     * 处理错误
     */
    protected function handleError(\Exception $e): void
    {
        $this->error("✗ 清理操作日志时发生错误: {$e->getMessage()}");

        Log::error("清理操作日志失败", [
            'tenant' => $this->getTenantName(),
            'error'  => $e->getMessage(),
            'trace'  => $e->getTraceAsString()
        ]);

        $this->stats['errors']++;
    }

    /**
     * 显示清理结果
     */
    protected function displayResults(): void
    {
        $this->newLine();
        $tenantName = $this->getTenantName();
        $this->info("租户 [{$tenantName}] 清理完成！");

        $this->table(['项目', '数量'], [
            ['删除的日志记录', $this->stats['deleted_logs']],
            ['错误数量', $this->stats['errors']]
        ]);
    }

    /**
     * 记录清理结果到日志
     */
    protected function logCleanupResults(Carbon $cutoffDate): void
    {
        Log::info('操作日志清理完成', [
            'tenant'      => $this->getTenantName(),
            'days'        => $this->getDays(),
            'cutoff_date' => $cutoffDate->toDateTimeString(),
            ...$this->stats
        ]);
    }

    /**
     * 获取清理天数
     */
    protected function getDays(): int
    {
        return (int)$this->option('days');
    }

    /**
     * 获取租户名称
     */
    protected function getTenantName(): string
    {
        return tenant('name');
    }
}
