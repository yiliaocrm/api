<?php

namespace App\Console\Commands\Tenant;

use App\Services\Workflow\WorkflowExecutionCleanupService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Stancl\Tenancy\Concerns\HasATenantsOption;
use Stancl\Tenancy\Concerns\TenantAwareCommand;
use Symfony\Component\Console\Command\Command as CommandAlias;
use Throwable;

class CleanupWorkflowExecutionsCommand extends Command
{
    use TenantAwareCommand, HasATenantsOption;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:cleanup-workflow-executions {--days=14 : 清理多少天前的工作流执行记录} {--chunk=500 : 每批清理的执行记录数量} {--dry-run : 仅统计，不执行删除}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '清理过期的工作流执行记录与步骤日志';

    /**
     * Create a new command instance.
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
        $days = max(1, (int) $this->option('days'));
        $chunk = max(1, (int) $this->option('chunk'));
        $dryRun = (bool) $this->option('dry-run');
        $cutoffDate = Carbon::now()->subDays($days);

        $this->displayStartMessage($days, $chunk, $dryRun, $cutoffDate);

        try {
            $stats = app(WorkflowExecutionCleanupService::class)
                ->cleanupExpiredExecutions($cutoffDate, $chunk, $dryRun);

            $this->displayResults($stats, $dryRun);
            $this->logCleanupResults($stats, $days, $chunk, $dryRun, $cutoffDate);

            return CommandAlias::SUCCESS;
        } catch (Throwable $e) {
            $this->displayError($e, $days, $chunk, $dryRun, $cutoffDate);

            return CommandAlias::FAILURE;
        }
    }

    /**
     * @param  array{matched_executions: int, deleted_steps: int, deleted_executions: int, batches: int}  $stats
     */
    private function displayResults(array $stats, bool $dryRun): void
    {
        if ($stats['matched_executions'] === 0) {
            $this->info(sprintf('租户 [%s] 没有找到需要清理的工作流执行记录', $this->tenantLabel()));

            return;
        }

        $this->newLine();
        if ($dryRun) {
            $this->info(sprintf('租户 [%s] 预览完成（未执行删除）', $this->tenantLabel()));
        } else {
            $this->info(sprintf('租户 [%s] 清理完成！', $this->tenantLabel()));
        }

        $this->table(['项目', '数量'], [
            ['匹配执行记录', $stats['matched_executions']],
            ['删除步骤日志', $stats['deleted_steps']],
            ['删除执行记录', $stats['deleted_executions']],
            ['批次数', $stats['batches']],
        ]);
    }

    private function displayStartMessage(int $days, int $chunk, bool $dryRun, Carbon $cutoffDate): void
    {
        $action = $dryRun ? '预览清理' : '清理';
        $this->info(sprintf('开始为租户 [%s] %s %d 天前的工作流执行记录...', $this->tenantLabel(), $action, $days));
        $this->line(sprintf('清理截止时间：%s', $cutoffDate->toDateTimeString()));
        $this->line(sprintf('批量大小：%d', $chunk));
    }

    /**
     * @param  array{matched_executions: int, deleted_steps: int, deleted_executions: int, batches: int}  $stats
     */
    private function logCleanupResults(
        array $stats,
        int $days,
        int $chunk,
        bool $dryRun,
        Carbon $cutoffDate
    ): void {
        Log::info('工作流执行记录清理完成', [
            'tenant' => $this->tenantLabel(),
            'days' => $days,
            'chunk' => $chunk,
            'dry_run' => $dryRun,
            'cutoff_date' => $cutoffDate->toDateTimeString(),
            ...$stats,
        ]);
    }

    private function displayError(
        Throwable $e,
        int $days,
        int $chunk,
        bool $dryRun,
        Carbon $cutoffDate
    ): void {
        $this->error(sprintf('租户 [%s] 清理失败: %s', $this->tenantLabel(), $e->getMessage()));

        Log::error('工作流执行记录清理失败', [
            'tenant' => $this->tenantLabel(),
            'days' => $days,
            'chunk' => $chunk,
            'dry_run' => $dryRun,
            'cutoff_date' => $cutoffDate->toDateTimeString(),
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);
    }

    private function tenantLabel(): string
    {
        $name = (string) (tenant('name') ?? '');
        if ($name !== '') {
            return $name;
        }

        $id = (string) (tenant('id') ?? '');

        return $id !== '' ? $id : 'unknown';
    }
}
