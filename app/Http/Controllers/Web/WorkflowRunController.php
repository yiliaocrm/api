<?php

namespace App\Http\Controllers\Web;

use App\Enums\WorkflowExecutionStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\WorkflowRunRequest;
use App\Models\Workflow;
use App\Models\WorkflowExecution;
use App\Models\WorkflowRun;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;

class WorkflowRunController extends Controller
{
    /**
     * 工作流下拉选项
     */
    public function workflows(WorkflowRunRequest $request): JsonResponse
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
     * 任务批次列表
     */
    public function index(WorkflowRunRequest $request): JsonResponse
    {
        $rows = $request->input('rows', 10);
        $workflowId = $request->input('workflow_id');
        $status = $request->input('status');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $sort = $request->input('sort', 'started_at');
        $order = $request->input('order', 'desc');

        $paginator = WorkflowRun::query()
            ->with(['workflow:id,name'])
            ->when($workflowId, fn (Builder $query) => $query->where('workflow_id', $workflowId))
            ->when($status, fn (Builder $query) => $query->where('status', $status))
            ->when($startDate, fn (Builder $query) => $query->where('created_at', '>=', Carbon::parse($startDate)->startOfDay()))
            ->when($endDate, fn (Builder $query) => $query->where('created_at', '<=', Carbon::parse($endDate)->endOfDay()))
            ->orderBy($sort, $order)
            ->paginate($rows);

        return response_success([
            'rows' => $paginator->items(),
            'total' => $paginator->total(),
        ]);
    }

    /**
     * 批次详情
     */
    public function detail(WorkflowRunRequest $request): JsonResponse
    {
        $run = WorkflowRun::query()
            ->with([
                'workflow:id,name,rule_chain',
                'workflowVersion:id,workflow_id,version_no,snapshot',
            ])
            ->findOrFail($request->input('id'));

        return response_success($run);
    }

    /**
     * 取消运行中的批次
     */
    public function cancel(WorkflowRunRequest $request): JsonResponse
    {
        $run = WorkflowRun::findOrFail($request->input('id'));

        if (! $run->isPending() && ! $run->isRunning()) {
            return response_error('只能取消待执行或执行中的批次');
        }

        // 先标记取消请求，供队列任务及时感知
        $run->requestCancel();

        // 清理运行中的 execution，避免继续推进
        WorkflowExecution::query()
            ->where('run_id', $run->id)
            ->whereIn('status', [
                WorkflowExecutionStatus::RUNNING->value,
                WorkflowExecutionStatus::WAITING->value,
            ])
            ->update([
                'status' => WorkflowExecutionStatus::CANCELED->value,
                'finished_at' => now(),
            ]);

        $run->cancel();

        return response_success($run, '批次已取消');
    }
}
