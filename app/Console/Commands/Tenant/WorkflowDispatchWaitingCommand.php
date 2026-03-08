<?php

namespace App\Console\Commands\Tenant;

use App\Jobs\Workflow\BatchRunWorkflowExecutionJob;
use App\Jobs\Workflow\RunWorkflowExecutionJob;
use App\Models\Admin\Tenant;
use App\Models\WorkflowExecution;
use App\Models\WorkflowRun;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

/**
 * 扫描等待中的工作流执行并投递恢复任务
 *
 * 支持两种模式：
 * 1. 按 run 批量恢复：查询指定运行记录中等待中的执行
 * 2. 全局恢复：查询所有等待中的执行（向后兼容）
 */
class WorkflowDispatchWaitingCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:workflow-dispatch-waiting-command {--limit=200 : 每个租户最多补偿的执行数} {--run-id= : 指定运行记录ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '扫描等待中的工作流执行并投递恢复任务';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $limit = max(1, (int) $this->option('limit'));
        $runId = $this->option('run-id') ? (int) $this->option('run-id') : null;

        Tenant::query()
            ->where('status', 'run')
            ->get()
            ->runForEach(function ($tenant) use ($limit, $runId) {
                if (! Schema::hasColumn('workflow_executions', 'waiting_until')) {
                    $this->warn(sprintf('tenant=%s skipped: missing workflow_executions.waiting_until', $tenant->id));

                    return;
                }

                // 如果指定了 run-id，则只处理该运行记录中的等待执行
                if ($runId) {
                    $this->recoverByRun($tenant, $runId, $limit);

                    return;
                }

                // 否则处理所有等待中的执行（向后兼容）
                $this->recoverAll($tenant, $limit);
            });

        return self::SUCCESS;
    }

    /**
     * 按运行记录恢复等待中的执行
     */
    private function recoverByRun(mixed $tenant, int $runId, int $limit): void
    {
        // 检查运行记录是否存在
        $run = WorkflowRun::query()->find($runId);

        if (! $run) {
            $this->warn(sprintf('tenant=%s run_id=%d not found', $tenant->id, $runId));

            return;
        }

        // 检查运行记录是否已取消
        if ($run->isCanceled()) {
            $this->info(sprintf('tenant=%s run_id=%d is canceled, skipping', $tenant->id, $runId));

            return;
        }

        // 查询该运行记录中等待中的执行
        $executions = WorkflowExecution::query()
            ->where('run_id', $runId)
            ->where('status', 'waiting')
            ->whereNotNull('waiting_until')
            ->where('waiting_until', '<=', now())
            ->orderBy('id')
            ->limit($limit)
            ->get(['id']);

        if ($executions->isEmpty()) {
            $this->info(sprintf('tenant=%s run_id=%d no waiting executions to recover', $tenant->id, $runId));

            return;
        }

        // 按批次投递 Job
        $executionIds = $executions->pluck('id')->toArray();
        $batchSize = 50;
        $batches = array_chunk($executionIds, $batchSize);

        foreach ($batches as $batch) {
            dispatch(new BatchRunWorkflowExecutionJob($batch, $runId));
        }

        $this->info(sprintf('tenant=%s run_id=%d recovered=%d batches=%d', $tenant->id, $runId, count($executionIds), count($batches)));
    }

    /**
     * 恢复所有等待中的执行（向后兼容）
     */
    private function recoverAll(mixed $tenant, int $limit): void
    {
        // 查询所有到期的等待执行（包括有和没有 run_id 的）
        $executions = WorkflowExecution::query()
            ->where('status', 'waiting')
            ->whereNotNull('waiting_until')
            ->where('waiting_until', '<=', now())
            ->orderBy('id')
            ->limit($limit)
            ->get(['id', 'run_id']);

        if ($executions->isEmpty()) {
            return;
        }

        // 显式拆分两类执行记录，避免 groupBy 将 null 键转为空字符串的问题
        $withoutRun = $executions->whereNull('run_id');
        $withRun = $executions->whereNotNull('run_id');

        // 触发型（run_id IS NULL）：逐条派发 RunWorkflowExecutionJob
        foreach ($withoutRun as $execution) {
            RunWorkflowExecutionJob::dispatch($execution->id);
        }

        // 周期型（run_id IS NOT NULL）：按 run_id 分组后批量派发
        $byRun = $withRun->groupBy('run_id');
        foreach ($byRun as $runId => $runExecutions) {
            // 检查 run 是否已取消
            $run = WorkflowRun::query()->find($runId);
            if (! $run || $run->isCanceled()) {
                continue;
            }

            $executionIds = $runExecutions->pluck('id')->toArray();
            $batchSize = 50;
            $batches = array_chunk($executionIds, $batchSize);

            foreach ($batches as $batch) {
                dispatch(new BatchRunWorkflowExecutionJob($batch, (int) $runId));
            }
        }

        $this->info(sprintf('tenant=%s recovered=%d (trigger=%d, periodic=%d)',
            $tenant->id, $executions->count(), $withoutRun->count(), $withRun->count()));
    }
}
