<?php

namespace App\Console\Commands\Tenant;

use Carbon\Carbon;
use App\Models\ExportTask;
use App\Enums\ExportTaskStatus;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Stancl\Tenancy\Concerns\HasATenantsOption;
use Stancl\Tenancy\Concerns\TenantAwareCommand;
use Symfony\Component\Console\Command\Command as CommandAlias;

class CleanupExportFilesCommand extends Command
{
    use TenantAwareCommand, HasATenantsOption;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:cleanup-export-files-command {--days=7 : 清理多少天前的文件}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '清理过期的导出文件';

    /**
     * 清理统计信息
     *
     * @var array
     */
    protected array $stats = [
        'deleted_files' => 0,
        'expired_tasks' => 0,
        'errors'        => 0,
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
        $tasks      = $this->getExpiredTasks($cutoffDate);

        if ($tasks->isEmpty()) {
            $this->displayNoTasksMessage();
            return CommandAlias::SUCCESS;
        }

        $this->displayFoundTasksMessage($tasks->count());
        $this->processTasks($tasks);
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

        $this->info("开始为租户 [{$tenantName}] 清理 {$days} 天前的导出文件...");
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
     * 获取过期的导出任务
     */
    protected function getExpiredTasks(Carbon $cutoffDate): Collection
    {
        return ExportTask::query()
            ->where('created_at', '<', $cutoffDate)
            ->where('status', '<>', ExportTaskStatus::EXPIRED)
            ->whereNotNull('file_path')
            ->get();
    }

    /**
     * 显示没有找到任务的消息
     */
    protected function displayNoTasksMessage(): void
    {
        $tenantName = $this->getTenantName();
        $this->info("租户 [{$tenantName}] 没有找到需要清理的导出任务");
    }

    /**
     * 显示找到任务的消息
     */
    protected function displayFoundTasksMessage(int $count): void
    {
        $tenantName = $this->getTenantName();
        $this->info("租户 [{$tenantName}] 找到 {$count} 个需要清理的导出任务");
    }

    /**
     * 处理所有任务
     */
    protected function processTasks(Collection $tasks): void
    {
        $tasks->each(fn(ExportTask $task) => $this->processTask($task));
    }

    /**
     * 处理单个任务
     */
    protected function processTask(ExportTask $task): void
    {
        try {
            $this->line("处理任务 ID: {$task->id}, 文件: {$task->file_path}");

            $this->deleteFileIfExists($task);
            $this->updateTaskStatus($task);

        } catch (\Exception $e) {
            $this->handleTaskError($task, $e);
        }
    }

    /**
     * 删除文件（如果存在）
     */
    protected function deleteFileIfExists(ExportTask $task): void
    {
        if (Storage::exists($task->file_path)) {
            Storage::delete($task->file_path);
            $this->info("  ✓ 删除文件: {$task->file_path}");
            $this->stats['deleted_files']++;
        } else {
            $this->comment("  - 文件不存在: {$task->file_path}");
        }
    }

    /**
     * 更新任务状态为过期
     */
    protected function updateTaskStatus(ExportTask $task): void
    {
        $task->update(['status' => ExportTaskStatus::EXPIRED]);
        $this->info("  ✓ 更新任务状态为文件过期 ID: {$task->id}");
        $this->stats['expired_tasks']++;
    }

    /**
     * 处理任务错误
     */
    protected function handleTaskError(ExportTask $task, \Exception $e): void
    {
        $this->error("  ✗ 处理任务 {$task->id} 时发生错误: {$e->getMessage()}");

        Log::error("清理导出文件失败", [
            'tenant'    => $this->getTenantName(),
            'task_id'   => $task->id,
            'file_path' => $task->file_path,
            'error'     => $e->getMessage()
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
            ['删除的文件', $this->stats['deleted_files']],
            ['过期的任务记录', $this->stats['expired_tasks']],
            ['错误数量', $this->stats['errors']]
        ]);
    }

    /**
     * 记录清理结果到日志
     */
    protected function logCleanupResults(Carbon $cutoffDate): void
    {
        Log::info('导出文件清理完成', [
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
