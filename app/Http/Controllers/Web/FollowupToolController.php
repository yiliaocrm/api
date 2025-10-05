<?php

namespace App\Http\Controllers\Web;

use App\Models\FollowupTool;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\FollowupToolRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Database\Eloquent\Builder;

class FollowupToolController extends Controller
{
    /**
     * 回访工具管理
     * @param Request $request
     * @return JsonResponse
     */
    public function manage(Request $request): JsonResponse
    {
        $rows  = $request->input('rows', 10);
        $sort  = $request->input('sort', 'id');
        $order = $request->input('order', 'desc');
        $query = FollowupTool::query()
            ->when($name = $request->input('name'), fn(Builder $query) => $query->whereLike('name', "%{$name}%"))
            ->orderBy($sort, $order)
            ->paginate($rows);

        return response_success([
            'rows'  => $query->items(),
            'total' => $query->total(),
        ]);
    }

    /**
     * 创建回访工具
     * @param FollowupToolRequest $request
     * @return JsonResponse
     */
    public function create(FollowupToolRequest $request): JsonResponse
    {
        $data = FollowupTool::query()->create(
            $request->formData()
        );
        return response_success($data);
    }

    /**
     * 更新回访工具
     * @param FollowupToolRequest $request
     * @return JsonResponse
     */
    public function update(FollowupToolRequest $request): JsonResponse
    {
        $data = FollowupTool::query()->find(
            $request->input('id')
        );
        $data->update(
            $request->formData()
        );
        return response_success($data);
    }

    /**
     * 删除回访
     * @param FollowupToolRequest $request
     * @return JsonResponse
     */
    public function remove(FollowupToolRequest $request): JsonResponse
    {
        FollowupTool::query()->find($request->input('id'))->delete();
        return response_success();
    }
}
