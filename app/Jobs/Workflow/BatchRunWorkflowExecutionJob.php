<?php

namespace App\Jobs\Workflow;

use App\Enums\WorkflowExecutionStatus;
use App\Models\WorkflowExecution;
use App\Models\WorkflowRun;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * 批量执行工作流 Job
 *
 * 负责批量执行多个工作流执行记录，每个批次处理 100~500 条。
 * 保留 execution/step 级可观测日志。
 */
class BatchRunWorkflowExecutionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly array $executionIds,
        public readonly int $runId
    ) {}

    /**
     * 执行任务
     */
    public function handle(): void
    {
        $run = WorkflowRun::query()->find($this->runId);

        if (! $run) {
            Log::warning('WorkflowRun not found for batch execution', ['run_id' => $this->runId]);

            return;
        }

        // 如果运行已取消，跳过
        if ($run->isCanceled()) {
            Log::info('WorkflowRun canceled, skipping batch execution', ['run_id' => $this->runId]);

            return;
        }

        // 获取待执行的 execution 记录（包括 waiting 状态，等待恢复场景）
        $executions = WorkflowExecution::query()
            ->whereIn('id', $this->executionIds)
            ->whereIn('status', [WorkflowExecutionStatus::RUNNING->value, WorkflowExecutionStatus::WAITING->value])
            ->get();

        if ($executions->isEmpty()) {
            Log::debug('No executions to process', ['run_id' => $this->runId, 'ids' => $this->executionIds]);

            $run->refresh();
            if ($run->dispatch_completed_at) {
                $run->tryConvergeTerminalState();
            }

            return;
        }

        $successCount = 0;
        $errorCount = 0;
        $skippedCount = 0;

        // 逐个执行
        foreach ($executions as $execution) {
            // 检查运行是否被取消
            $run->refresh();
            if ($run->isCancelRequested()) {
                Log::info('WorkflowRun canceled during batch execution', ['run_id' => $this->runId]);
                break;
            }

            // 检查 execution 状态是否为 running 或 waiting（等待恢复）
            if (! in_array($execution->status, [WorkflowExecutionStatus::RUNNING, WorkflowExecutionStatus::WAITING], true)) {
                $skippedCount++;
                Log::debug('Execution skipped, not in running/waiting status', ['execution_id' => $execution->id, 'status' => $execution->status]);

                continue;
            }

            try {
                // 执行单个 execution
                $this->executeExecution($execution);
                $successCount++;
            } catch (\Throwable $e) {
                $errorCount++;
                Log::error('Execution failed in batch', [
                    'execution_id' => $execution->id,
                    'error' => $e->getMessage(),
                ]);

                // 标记 execution 为 error
                $execution->update([
                    'status' => WorkflowExecutionStatus::ERROR->value,
                    'error_message' => $e->getMessage(),
                    'finished_at' => now(),
                ]);
            }
        }

        // 更新运行记录的计数
        if ($successCount > 0) {
            $run->incrementSuccess($successCount);
        }
        if ($errorCount > 0) {
            $run->incrementError($errorCount);
        }
        $run->incrementProcessed($successCount + $errorCount + $skippedCount);

        $run->refresh();
        if ($run->dispatch_completed_at) {
            $run->tryConvergeTerminalState();
        }

        Log::debug('Batch execution completed', [
            'run_id' => $this->runId,
            'success' => $successCount,
            'error' => $errorCount,
            'skipped' => $skippedCount,
        ]);
    }

    /**
     * 执行单个 execution
     *
     * 这里复用 RunWorkflowExecutionJob 的处理逻辑
     */
    private function executeExecution(WorkflowExecution $execution): void
    {
        // 直接调用 Job 处理
        $job = new RunWorkflowExecutionJob($execution->id);
        $job->handle();
    }
}
