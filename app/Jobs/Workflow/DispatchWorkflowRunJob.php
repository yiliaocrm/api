<?php

namespace App\Jobs\Workflow;

use App\Enums\WorkflowExecutionStatus;
use App\Models\Customer;
use App\Models\Workflow;
use App\Models\WorkflowExecution;
use App\Models\WorkflowRun;
use App\Models\WorkflowVersion;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Throwable;

/**
 * 工作流分发 Job
 *
 * 负责将周期型工作流分发给目标客户，按游标分片拉取客户，批量创建执行记录，
 * 并投递批执行 Job。
 */
class DispatchWorkflowRunJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * 最大循环次数，防止无限循环
     */
    private const int MAX_LOOP_COUNT = 100;

    /**
     * 队列深度检查间隔
     */
    private const int QUEUE_LAG_CHECK_INTERVAL = 5;

    public function __construct(
        public readonly int $runId
    ) {}

    /**
     * 执行任务
     */
    public function handle(): void
    {
        $lockKey = "workflow-run-dispatch:{$this->runId}";
        $lock = Cache::lock($lockKey, 300);

        if (! $lock->get()) {
            Log::info('DispatchWorkflowRunJob lock acquisition failed, skipping', ['run_id' => $this->runId]);

            return;
        }

        try {
            $this->doDispatch();
        } finally {
            $lock->release();
        }
    }

    /**
     * 分发主流程
     */
    private function doDispatch(): void
    {
        $run = WorkflowRun::query()
            ->with([
                'workflow' => function ($query) {
                    $query->select('id', 'name', 'dispatch_chunk_size', 'dispatch_concurrency', 'execution_batch_size', 'max_queue_lag', 'all_customer');
                },
                'workflow.customerGroups:id',
            ])
            ->find($this->runId);

        if (! $run) {
            Log::warning('WorkflowRun not found', ['run_id' => $this->runId]);

            return;
        }

        if ($run->isTerminal()) {
            Log::info('WorkflowRun already terminal, skipping dispatch', [
                'run_id' => $this->runId,
                'status' => $run->status->value,
            ]);

            return;
        }

        if ($run->isPending()) {
            $run->start();
            $run->refresh();
        }

        if ($run->isCancelRequested()) {
            $run->cancel();
            Log::info('WorkflowRun cancel requested before dispatch', ['run_id' => $this->runId]);

            return;
        }

        $workflow = $run->workflow;
        if (! $workflow) {
            $run->fail('Workflow not found');
            Log::error('Workflow not found', ['workflow_id' => $run->workflow_id]);

            return;
        }

        // 获取最新版本
        $latestVersion = WorkflowVersion::query()
            ->where('workflow_id', $workflow->id)
            ->orderByDesc('version_no')
            ->first();

        // 更新运行记录的版本ID
        if ($latestVersion && ! $run->workflow_version_id) {
            $run->update(['workflow_version_id' => $latestVersion->id]);
        }

        // 配置参数
        $chunkSize = (int) ($workflow->dispatch_chunk_size ?? 2000);
        $executionBatchSize = (int) ($workflow->execution_batch_size ?? 200);
        $maxQueueLag = (int) ($workflow->max_queue_lag ?? 1000);

        // 计算目标总数（首次运行时）
        if ($run->total_target === 0) {
            $totalTarget = $this->calculateTotalTarget($workflow);
            $run->setTotalTarget($totalTarget);
        }

        // 开始分片处理
        $loopCount = 0;

        while ($loopCount < self::MAX_LOOP_COUNT) {
            // 检查是否请求了取消
            $run->refresh();
            if ($run->isCancelRequested()) {
                $run->cancel();
                Log::info('WorkflowRun canceled during dispatch', ['run_id' => $this->runId]);

                return;
            }

            if ($run->isTerminal()) {
                Log::info('WorkflowRun reached terminal state during dispatch loop', [
                    'run_id' => $this->runId,
                    'status' => $run->status->value,
                ]);

                return;
            }

            // 背压控制：局部积压 + 全局队列深度
            if ($loopCount > 0 && $loopCount % self::QUEUE_LAG_CHECK_INTERVAL === 0
                && $this->shouldPauseForBackpressure($run, $maxQueueLag)) {
                $this->scheduleContinuation($run, 10);

                return;
            }

            // 检查是否还有更多客户
            $chunkResult = $this->dispatchNextChunk($run, $workflow, $latestVersion, $chunkSize, $executionBatchSize);
            $hasMore = $chunkResult['has_more'];

            if (! $hasMore) {
                // 分发已完成，等待执行层收敛后再置 completed
                if (! $run->dispatch_completed_at) {
                    $run->update(['dispatch_completed_at' => now()]);
                }

                $run->refresh();
                $converged = $run->tryConvergeTerminalState();
                Log::info('WorkflowRun completed', [
                    'run_id' => $this->runId,
                    'total_target' => $run->total_target,
                    'enqueued_count' => $run->enqueued_count,
                    'converged' => $converged,
                ]);

                return;
            }

            $loopCount++;
        }

        // 达到单次循环上限后自动续跑
        $run->refresh();
        $this->scheduleContinuation($run, 5);

        Log::debug('Dispatch loop ended', [
            'run_id' => $this->runId,
            'loop_count' => $loopCount,
            'enqueued_count' => $run->enqueued_count,
        ]);
    }

    /**
     * 分发下一批客户
     */
    private function dispatchNextChunk(
        WorkflowRun $run,
        Workflow $workflow,
        ?WorkflowVersion $latestVersion,
        int $chunkSize,
        int $executionBatchSize
    ): array {
        // 构建目标客户查询
        $customerQuery = $this->buildTargetCustomerQuery($workflow);

        // 使用游标分页
        if ($run->cursor_last_customer_id) {
            $customerQuery->where('customer.id', '>', $run->cursor_last_customer_id);
        }

        // 获取下一批客户
        $customers = $customerQuery
            ->select('customer.id')
            ->orderBy('customer.id')
            ->limit($chunkSize)
            ->get();

        if ($customers->isEmpty()) {
            return [
                'has_more' => false,
                'new_execution_count' => 0,
            ];
        }

        $now = now();
        $lastCustomerId = null;
        $executionsData = [];
        $triggerModelIds = [];

        // 构建执行数据
        foreach ($customers as $customer) {
            $lastCustomerId = $customer->id;
            $triggerModelId = (string) $customer->id;
            $triggerModelIds[] = $triggerModelId;

            $executionsData[] = [
                'workflow_id' => $workflow->id,
                'workflow_version_id' => $latestVersion?->id,
                'run_id' => $run->id,
                'status' => 'running',
                'started_at' => null,
                'input_data' => json_encode(['customer_id' => $customer->id]),
                'trigger_type' => 'periodic',
                'trigger_event' => 'periodic.scheduled',
                'trigger_model_type' => 'customer',
                'trigger_model_id' => $triggerModelId,
                'context_data' => json_encode([
                    'trigger' => [
                        'event' => 'periodic.scheduled',
                        'type' => 'periodic',
                        'customer_id' => $triggerModelId,
                        'workflow_id' => $workflow->id,
                        'run_id' => $run->id,
                        'tenant_id' => tenant('id'),
                        'triggered_at' => $now->toIso8601String(),
                    ],
                    'payload' => [],
                    'runtime' => [
                        'steps' => [],
                        'node_outputs' => [],
                    ],
                ]),
                'lock_version' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        $existingTriggerIds = WorkflowExecution::query()
            ->where('workflow_id', $workflow->id)
            ->where('run_id', $run->id)
            ->where('trigger_model_type', 'customer')
            ->whereIn('trigger_model_id', $triggerModelIds)
            ->pluck('trigger_model_id')
            ->map(static fn ($id) => (string) $id)
            ->all();

        $newTriggerIds = array_values(array_diff($triggerModelIds, $existingTriggerIds));

        // 批量插入执行记录（使用 upsert 保证幂等性）
        if (! empty($executionsData)) {
            WorkflowExecution::query()->upsert(
                $executionsData,
                ['workflow_id', 'run_id', 'trigger_model_type', 'trigger_model_id'],
                ['input_data', 'context_data', 'updated_at']
            );
        }

        $executionIds = collect();
        if (! empty($newTriggerIds)) {
            // 仅派发本轮新建 execution，避免重复派发历史 running execution
            $executionIds = WorkflowExecution::query()
                ->where('workflow_id', $workflow->id)
                ->where('run_id', $run->id)
                ->where('trigger_model_type', 'customer')
                ->whereIn('trigger_model_id', $newTriggerIds)
                ->where('status', WorkflowExecutionStatus::RUNNING->value)
                ->pluck('id');
        }

        // 将执行 ID 按批次分组并投递 Job
        $executionIdsArray = $executionIds->toArray();
        $batches = array_chunk($executionIdsArray, $executionBatchSize);

        foreach ($batches as $batch) {
            dispatch(new BatchRunWorkflowExecutionJob($batch, $run->id));
        }

        // 更新游标
        if ($lastCustomerId) {
            $run->advanceCursor($lastCustomerId);
        }

        // 更新入队计数
        $newExecutionCount = count($executionIdsArray);
        if ($newExecutionCount > 0) {
            $run->incrementEnqueued($newExecutionCount);
        }

        Log::debug('Dispatched chunk', [
            'run_id' => $run->id,
            'customer_count' => $customers->count(),
            'new_execution_count' => $newExecutionCount,
            'batch_count' => count($batches),
            'last_customer_id' => $lastCustomerId,
            'duplicate_customer_count' => count($triggerModelIds) - count($newTriggerIds),
        ]);

        return [
            'has_more' => $customers->count() === $chunkSize,
            'new_execution_count' => $newExecutionCount,
        ];
    }

    /**
     * 计算目标客户总数
     */
    private function calculateTotalTarget(Workflow $workflow): int
    {
        $query = $this->buildTargetCustomerQuery($workflow);

        return $query->count();
    }

    /**
     * 根据工作流的目标人群配置构建客户查询
     */
    private function buildTargetCustomerQuery(Workflow $workflow)
    {
        $query = Customer::query();

        // all_customer=true 时查询全部客户
        if ((bool) $workflow->all_customer) {
            return $query;
        }

        // 获取关联的客户分组ID列表
        $groupIds = $workflow->customerGroups
            ->pluck('id')
            ->map(static fn ($id) => (int) $id)
            ->filter(static fn (int $id) => $id > 0)
            ->values()
            ->all();

        if (empty($groupIds)) {
            // 无分组关联，返回空查询
            return $query->whereRaw('1 = 0');
        }

        // 通过 customer_group_details 关联查询目标客户
        return $query
            ->select('customer.id')
            ->join('customer_group_details', 'customer_group_details.customer_id', '=', 'customer.id')
            ->whereIn('customer_group_details.customer_group_id', $groupIds)
            ->distinct();
    }

    /**
     * 判断是否需要因背压暂停分发
     */
    private function shouldPauseForBackpressure(WorkflowRun $run, int $maxQueueLag): bool
    {
        if ($maxQueueLag <= 0) {
            return false;
        }

        $localLag = max(0, (int) $run->enqueued_count - (int) $run->processed_count);
        if ($localLag >= $maxQueueLag) {
            Log::info('Backpressure hit by local lag', [
                'run_id' => $this->runId,
                'local_lag' => $localLag,
                'threshold' => $maxQueueLag,
            ]);

            return true;
        }

        try {
            $queueSize = (int) Queue::size();
            if ($queueSize >= $maxQueueLag) {
                Log::info('Backpressure hit by queue depth', [
                    'run_id' => $this->runId,
                    'queue_size' => $queueSize,
                    'threshold' => $maxQueueLag,
                ]);

                return true;
            }
        } catch (Throwable $exception) {
            Log::warning('Queue size check failed, fallback to local lag only', [
                'run_id' => $this->runId,
                'error' => $exception->getMessage(),
            ]);
        }

        return false;
    }

    /**
     * 续投分发任务
     */
    private function scheduleContinuation(WorkflowRun $run, int $delaySeconds = 5): void
    {
        $run->refresh();
        if (! $run->isRunning() || $run->isCancelRequested() || $run->isTerminal()) {
            return;
        }

        dispatch(new self($this->runId))->delay(now()->addSeconds(max(1, $delaySeconds)));

        Log::info('DispatchWorkflowRunJob continuation scheduled', [
            'run_id' => $this->runId,
            'delay_seconds' => $delaySeconds,
        ]);
    }
}
