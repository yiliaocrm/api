<?php

namespace App\Http\Controllers\Web;

use App\Exports\ImportTaskDetailExport;
use Throwable;
use App\Models\ImportTask;
use App\Models\ImportTemplate;
use App\Services\ImportService;
use App\Models\ImportTaskDetail;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\ImportTaskRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Database\Eloquent\Builder;

class ImportTaskController extends Controller
{
    /**
     * 导入任务列表
     * @param ImportTaskRequest $request
     * @return JsonResponse
     */
    public function index(ImportTaskRequest $request): JsonResponse
    {
        $rows      = $request->input('rows', 10);
        $sort      = $request->input('sort', 'id');
        $order     = $request->input('order', 'desc');
        $file_name = $request->input('file_name');

        $query = ImportTask::query()
            ->with([
                'template:id,title',
            ])
            ->when($file_name, fn(Builder $query) => $query->whereLike('file_name', '%' . $file_name . '%'))
            ->orderBy($sort, $order)
            ->paginate($rows);

        $query->append(['status_text']);

        return response_success([
            'rows'  => $query->items(),
            'total' => $query->total()
        ]);
    }

    /**
     * 创建导入任务
     * @param ImportTaskRequest $request
     * @param ImportService $importService
     * @return JsonResponse
     */
    public function create(ImportTaskRequest $request, ImportService $importService): JsonResponse
    {
        $template = ImportTemplate::query()->find(
            $request->input('template_id')
        );
        try {
            $importService->prepare($template, $request->file('file'));
            return response_success();
        } catch (Throwable $e) {
            return response_error(msg: $e->getMessage());
        }
    }

    /**
     * 导入任务明细
     * @param ImportTaskRequest $request
     * @return JsonResponse
     */
    public function details(ImportTaskRequest $request): JsonResponse
    {
        $taskId = $request->input('id');
        $rows   = $request->input('rows', 10);
        $sort   = $request->input('sort', 'id');
        $order  = $request->input('order', 'desc');
        $status = $request->input('status');

        // 查询任务主表信息
        $task = ImportTask::query()
            ->with(['template:id,title'])
            ->find($taskId);

        // 查询明细表数据，支持分页和状态筛选
        $detailsQuery = ImportTaskDetail::query()
            ->where('task_id', $taskId)
            ->when($status !== null, fn(Builder $query) => $query->where('status', $status))
            ->orderBy($sort, $order)
            ->paginate($rows);

        // 添加状态文本
        $detailsQuery->append(['status_text']);

        return response_success([
            'task'    => $task,
            'details' => [
                'rows'  => $detailsQuery->items(),
                'total' => $detailsQuery->total()
            ]
        ]);
    }

    /**
     * 执行导入任务
     * @param ImportTaskRequest $request
     * @param ImportService $importService
     * @return JsonResponse
     */
    public function import(ImportTaskRequest $request, ImportService $importService)
    {
        $importService->import(
            $request->input('id')
        );
        return response_success();
    }

    /**
     * 导出明细数据
     * @param ImportTaskRequest $request
     * @return JsonResponse
     */
    public function export(ImportTaskRequest $request): JsonResponse
    {
        $name = $request->input('fileName', '导入明细数据');

        // 获取导入任务信息用于文件命名
        $task = ImportTask::query()->find($request->input('task_id'));
        if ($task) {
            $name = $task->file_name . '_导入明细';
        }

        // 创建导出任务
        $exportTask = $request->createExportTask($name);

        // 分派异步导出任务
        dispatch(new ImportTaskDetailExport($request->all(), $exportTask, user()->id));

        return response_success();
    }
}
