<?php

namespace App\Console\Commands\Tenant;

use App\Jobs\Workflow\DispatchWorkflowRunJob;
use App\Models\Admin\Tenant;
use App\Models\Workflow;
use App\Models\WorkflowRun;
use App\Services\Workflow\WorkflowPeriodicScheduler;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class WorkflowDispatchPeriodicCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:workflow-dispatch-periodic-command {--limit=50 : 每个租户每轮最多调度的周期型工作流数}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '扫描到期的周期型工作流并创建运行记录';

    public function __construct(private readonly WorkflowPeriodicScheduler $scheduler)
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $limit = max(1, (int) $this->option('limit'));

        Tenant::query()
            ->where('status', 'run')
            ->get()
            ->runForEach(function ($tenant) use ($limit) {
                if (! Schema::hasTable('workflows') || ! Schema::hasColumn('workflows', 'next_run_at')) {
                    $this->warn(sprintf('tenant=%s skipped: missing workflows table or next_run_at column', $tenant->id));

                    return;
                }

                if (! Schema::hasTable('workflow_runs')) {
                    $this->warn(sprintf('tenant=%s skipped: missing workflow_runs table', $tenant->id));

                    return;
                }

                $this->dispatchPeriodicWorkflows($tenant, $limit);
            });

        return self::SUCCESS;
    }

    /**
     * 为单个租户调度到期的周期型工作流
     */
    private function dispatchPeriodicWorkflows(mixed $tenant, int $limit): void
    {
        $workflows = Workflow::query()
            ->where('type', 'periodic')
            ->where('status', 'active')
            ->whereNotNull('next_run_at')
            ->where('next_run_at', '<=', now())
            ->with('customerGroups:id')
            ->orderBy('next_run_at')
            ->limit($limit)
            ->get();

        if ($workflows->isEmpty()) {
            return;
        }

        $dispatched = 0;

        foreach ($workflows as $workflow) {
            try {
                $count = $this->dispatchSingleWorkflow($workflow);
                $dispatched += $count;
            } catch (\Throwable $e) {
                Log::error('周期型工作流调度失败', [
                    'workflow_id' => $workflow->id,
                    'error' => $e->getMessage(),
                ]);
                $this->error(sprintf('workflow=%d error: %s', $workflow->id, $e->getMessage()));

                // 即使调度失败，也必须推进 next_run_at，防止 next_run_at 停滞导致下一分钟无限重试循环
                try {
                    $periodicConfig = $this->scheduler->extractPeriodicConfig(
                        is_array($workflow->rule_chain) ? $workflow->rule_chain : []
                    );
                    $workflow->last_run_at = Carbon::now();
                    $workflow->next_run_at = $periodicConfig
                        ? $this->scheduler->calculateNextRunAt($periodicConfig, Carbon::now())
                        : null;
                    $workflow->save();
                } catch (\Throwable $saveException) {
                    Log::error('周期型工作流 next_run_at 推进失败', [
                        'workflow_id' => $workflow->id,
                        'error' => $saveException->getMessage(),
                    ]);
                }
            }
        }

        $this->info(sprintf('tenant=%s workflows=%d runs=%d', $tenant->id, $workflows->count(), $dispatched));
    }

    /**
     * 调度单个周期型工作流：创建运行记录并投递 DispatchJob
     */
    private function dispatchSingleWorkflow(Workflow $workflow): int
    {
        $now = Carbon::now();

        // 生成幂等键：使用分钟级时间戳
        $runKey = $now->format('YmdHi');

        // 检查是否已存在该时间点的运行记录（幂等）
        $existingRun = WorkflowRun::query()
            ->where('workflow_id', $workflow->id)
            ->where('run_key', $runKey)
            ->first();

        if ($existingRun) {
            // 如果已存在且未完成，返回 0（不重复创建）
            if (in_array($existingRun->status, ['pending', 'running'], true)) {
                $this->info(sprintf('WorkflowRun already exists and is %s, skipping', $existingRun->status), [
                    'workflow_id' => $workflow->id,
                    'run_id' => $existingRun->id,
                ]);

                return 0;
            }
        }

        // 确定目标模式
        $targetMode = $workflow->all_customer ? 'all' : 'groups';
        $groupIds = $workflow->customerGroups->pluck('id')->toArray();

        // 创建运行记录
        $run = WorkflowRun::query()->create([
            'workflow_id' => $workflow->id,
            'workflow_version_id' => null, // 将在 DispatchJob 中更新
            'run_key' => $runKey,
            'status' => 'pending',
            'target_mode' => $targetMode,
            'group_ids_json' => $targetMode === 'groups' ? $groupIds : null,
            'cursor_last_customer_id' => null,
            'total_target' => 0,
            'enqueued_count' => 0,
            'processed_count' => 0,
            'success_count' => 0,
            'error_count' => 0,
            'cancel_requested_at' => null,
            'started_at' => null,
            'finished_at' => null,
            'error_message' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // 投递 DispatchJob
        dispatch(new DispatchWorkflowRunJob($run->id));

        // 更新工作流调度时间
        $periodicConfig = $this->scheduler->extractPeriodicConfig(
            is_array($workflow->rule_chain) ? $workflow->rule_chain : []
        );

        $workflow->last_run_at = $now;
        // 使用实时时间作为计算基准，确保 next_run_at 一定晚于本次执行时间
        $workflow->next_run_at = $periodicConfig
            ? $this->scheduler->calculateNextRunAt($periodicConfig, Carbon::now())
            : null;
        $workflow->save();

        $this->info(sprintf('Created WorkflowRun for workflow=%d, run_id=%d', $workflow->id, $run->id));

        return 1;
    }
}
