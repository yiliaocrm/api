<?php

namespace App\Http\Controllers\Web;

use App\Models\ExportTask;
use App\Models\ImportTask;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;

class MessageController extends Controller
{
    /**
     * [导出任务]消息列表
     * @param Request $request
     * @return JsonResponse
     */
    public function export(Request $request): JsonResponse
    {
        $sort  = $request->input('sort', 'id');
        $rows  = $request->input('rows', 10);
        $order = $request->input('order', 'desc');
        $query = ExportTask::query()
            ->where('user_id', user()->id)
            ->orderBy($sort, $order)
            ->paginate($rows);

        $query->append(['status_text']);

        return response_success([
            'rows'  => $query->items(),
            'total' => $query->total(),
        ]);
    }

    /**
     * [导入任务]消息列表
     * @param Request $request
     * @return JsonResponse
     */
    public function import(Request $request): JsonResponse
    {
        $sort  = $request->input('sort', 'id');
        $rows  = $request->input('rows', 10);
        $order = $request->input('order', 'desc');
        $query = ImportTask::query()
            ->where('create_user_id', user()->id)
            ->orderBy($sort, $order)
            ->paginate($rows);

        $query->append(['status_text']);

        return response_success([
            'rows'  => $query->items(),
            'total' => $query->total(),
        ]);
    }
}
