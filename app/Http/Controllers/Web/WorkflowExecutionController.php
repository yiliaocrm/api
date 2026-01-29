<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\WorkflowExecutionRequest;
use App\Models\WorkflowExecution;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;

class WorkflowExecutionController extends Controller
{
    /**
     * 执行记录列表
     */
    public function index(WorkflowExecutionRequest $request): JsonResponse
    {
        $rows = $request->input('rows', 10);
        $sort = $request->input('sort', 'started_at');
        $order = $request->input('order', 'desc');

        $query = WorkflowExecution::query()
            ->with([
                'workflow:id,name',
                'triggerUser:id,name',
            ])
            ->when($request->input('workflow_id'), fn (Builder $query) => $query->where('workflow_id', $request->input('workflow_id')))
            ->when($request->input('status'), fn (Builder $query) => $query->where('status', $request->input('status')))
            ->when($request->input('trigger_type'), fn (Builder $query) => $query->where('trigger_type', $request->input('trigger_type')))
            ->when($request->input('start_date'), fn (Builder $query) => $query->where('started_at', '>=', $request->input('start_date')))
            ->when($request->input('end_date'), fn (Builder $query) => $query->where('started_at', '<=', $request->input('end_date')))
            ->orderBy($sort, $order)
            ->paginate($rows);

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
            'workflow:id,name,nodes,connections',
            'triggerUser:id,name',
        ])->findOrFail($request->input('id'));

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
