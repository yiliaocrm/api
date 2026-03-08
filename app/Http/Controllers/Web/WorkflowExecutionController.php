<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\WorkflowExecutionRequest;
use App\Models\Workflow;
use App\Models\WorkflowExecution;
use App\Models\WorkflowVersion;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;

class WorkflowExecutionController extends Controller
{
    /**
     * 工作流下拉选项
     */
    public function workflows(WorkflowExecutionRequest $request): JsonResponse
    {
        $keyword = trim($request->input('keyword', ''));

        $workflows = Workflow::query()
            ->select(['id', 'name'])
            ->when($keyword !== '', fn (Builder $query) => $query->where('name', 'like', "%{$keyword}%"))
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->get();

        return response_success($workflows);
    }

    /**
     * 执行记录列表
     */
    public function index(WorkflowExecutionRequest $request): JsonResponse
    {
        $rows = $request->input('rows', 10);
        $executionId = $request->input('execution_id', 0);
        $workflowId = $request->input('workflow_id', 0);
        $workflowVersionId = $request->input('workflow_version_id', 0);
        $status = $request->input('status');
        $triggerType = $request->input('trigger_type');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $latestVersionOnly = $request->boolean('latest_version_only');
        $allowedSorts = ['id', 'status', 'started_at', 'finished_at', 'duration', 'created_at', 'updated_at'];
        $sort = in_array($request->input('sort', 'started_at'), $allowedSorts, true)
            ? $request->input('sort', 'started_at')
            : 'started_at';

        $order = strtolower($request->input('order', 'desc'));
        $order = in_array($order, ['asc', 'desc'], true) ? $order : 'desc';

        $query = WorkflowExecution::query()
            ->with([
                'workflow:id,name',
                'triggerUser:id,name',
            ])
            ->when($executionId > 0, fn (Builder $query) => $query->where('id', $executionId))
            ->when($workflowId, fn (Builder $query) => $query->where('workflow_id', $workflowId))
            ->when($status, fn (Builder $query) => $query->where('status', $status))
            ->when($triggerType, fn (Builder $query) => $query->where('trigger_type', $triggerType))
            ->when($startDate, fn (Builder $query) => $query->where('started_at', '>=', Carbon::parse($startDate)->startOfDay()))
            ->when($endDate, fn (Builder $query) => $query->where('started_at', '<=', Carbon::parse($endDate)->endOfDay()));

        if ($workflowVersionId > 0) {
            $query->where('workflow_version_id', $workflowVersionId);
        } elseif ($latestVersionOnly && $workflowId > 0) {
            $latestVersionId = (int) WorkflowVersion::query()
                ->where('workflow_id', $workflowId)
                ->orderByDesc('version_no')
                ->orderByDesc('id')
                ->value('id');

            if ($latestVersionId <= 0) {
                return response_success([
                    'rows' => [],
                    'total' => 0,
                ]);
            }

            $query->where('workflow_version_id', $latestVersionId);
        }

        $query = $query->orderBy($sort, $order)->paginate($rows);

        return response_success([
            'rows' => $query->items(),
            'total' => $query->total(),
        ]);
    }

    /**
     * 执行记录详情
     */
    public function detail(WorkflowExecutionRequest $request): JsonResponse
    {
        $execution = WorkflowExecution::with([
            'workflow:id,name,rule_chain',
            'workflowVersion:id,workflow_id,version_no,snapshot',
            'triggerUser:id,name',
            'steps' => fn ($query) => $query->orderBy('id'),
        ])->findOrFail($request->input('id'));

        // 批量解析所有步骤的输入数据
        $resolver = app(\App\Services\Workflow\StepDataResolver::class);
        $resolvedInputs = $resolver->getBatchInputs($execution->steps);

        // 为每个步骤附加 resolved_input
        $execution->steps->each(function ($step) use ($resolvedInputs) {
            $step->resolved_input = $resolvedInputs[$step->id] ?? null;
        });

        return response_success($execution);
    }

    /**
     * 重试失败的执行
     */
    public function retry(WorkflowExecutionRequest $request): JsonResponse
    {
        $execution = WorkflowExecution::findOrFail($request->input('id'));

        // 验证是否可以重试
        if ($execution->status->value !== 'error') {
            return response_error('只能重试失败的执行记录');
        }

        // 创建新的执行记录
        $newExecution = $execution->replicate();
        $newExecution->status = 'running';
        $newExecution->started_at = now();
        $newExecution->finished_at = null;
        $newExecution->duration = null;
        $newExecution->error_message = null;
        $newExecution->output_data = null;
        $newExecution->trigger_user_id = auth()->id();
        $newExecution->save();

        // TODO: 实际执行工作流逻辑（第二阶段对接 n8n 时实现）

        return response_success($newExecution, '已创建重试任务');
    }

    /**
     * 取消正在运行的执行
     */
    public function cancel(WorkflowExecutionRequest $request): JsonResponse
    {
        $execution = WorkflowExecution::findOrFail($request->input('id'));

        // 验证是否可以取消
        if (! in_array($execution->status->value, ['running', 'waiting'])) {
            return response_error('只能取消运行中或等待中的执行记录');
        }

        $execution->update([
            'status' => 'canceled',
            'finished_at' => now(),
            'duration' => $execution->started_at ? now()->diffInMilliseconds($execution->started_at) : null,
        ]);

        // TODO: 调用 n8n API 取消执行（第二阶段实现）

        return response_success($execution, '执行已取消');
    }
}
