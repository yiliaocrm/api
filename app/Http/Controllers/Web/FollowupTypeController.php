<?php

namespace App\Http\Controllers\Web;

use App\Models\FollowupType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\FollowupTypeRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Database\Eloquent\Builder;

class FollowupTypeController extends Controller
{
    /**
     * 回访类型管理
     * @param Request $request
     * @return JsonResponse
     */
    public function manage(Request $request): JsonResponse
    {
        $rows  = $request->input('rows', 10);
        $sort  = $request->input('sort', 'id');
        $order = $request->input('order', 'desc');
        $query = FollowupType::query()
            ->when($request->input('name'), function (Builder $builder) use ($request) {
                $builder->where('name', 'like', '%' . $request->input('name') . '%');
            })
            ->orderBy($sort, $order)
            ->paginate($rows);

        return response_success([
            'rows'  => $query->items(),
            'total' => $query->total(),
        ]);
    }

    /**
     * 创建回访类型
     * @param FollowupTypeRequest $request
     * @return JsonResponse
     */
    public function create(FollowupTypeRequest $request): JsonResponse
    {
        $type = FollowupType::query()->create(
            $request->formData()
        );
        return response_success($type);
    }

    /**
     * 更新回访类型
     * @param FollowupTypeRequest $request
     * @return JsonResponse
     */
    public function update(FollowupTypeRequest $request): JsonResponse
    {
        $type = FollowupType::query()->find(
            $request->input('id')
        );
        $type->update(
            $request->formData()
        );
        return response_success($type);
    }

    /**
     * 删除类型
     * @param FollowupTypeRequest $request
     * @return JsonResponse
     */
    public function remove(FollowupTypeRequest $request): JsonResponse
    {
        FollowupType::query()->find($request->input('id'))->delete();
        return response_success();
    }
}
