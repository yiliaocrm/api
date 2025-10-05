<?php

namespace App\Http\Controllers\Web;

use App\Models\UserGroup;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\UserGroupRequest;
use Illuminate\Database\Eloquent\Builder;

class UserGroupController extends Controller
{
    /**
     * 工作组列表
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $rows  = $request->input('rows', 10);
        $sort  = $request->input('sort', 'id');
        $order = $request->input('order', 'desc');
        $name  = $request->input('name');

        $query = UserGroup::query()
            ->with([
                'store:id,name',
                'details:id,name',
                'createUser:id,name',
            ])
            ->when($name, fn(Builder $query) => $query->where('name', 'like', "%{$name}%"))
            ->orderBy($sort, $order)
            ->paginate($rows);

        return response_success([
            'rows'  => $query->items(),
            'total' => $query->total()
        ]);
    }

    /**
     * 创建工作组
     * @param UserGroupRequest $request
     * @return JsonResponse
     */
    public function create(UserGroupRequest $request): JsonResponse
    {
        $userGroup = UserGroup::query()->create(
            $request->formData()
        );

        // 添加成员
        $userGroup->details()->sync(
            $request->input('ids')
        );

        return response_success($userGroup);
    }

    /**
     * 更新工作组
     * @param UserGroupRequest $request
     * @return JsonResponse
     */
    public function update(UserGroupRequest $request): JsonResponse
    {
        $userGroup = UserGroup::query()->find(
            $request->input('id')
        );

        $userGroup->update(
            $request->formData()
        );

        // 更新成员
        $userGroup->details()->sync(
            $request->input('ids')
        );

        return response_success($userGroup);
    }

    /**
     * 删除工作组
     * @param UserGroupRequest $request
     * @return JsonResponse
     */
    public function remove(UserGroupRequest $request): JsonResponse
    {
        $userGroup = UserGroup::query()->find($request->input('id'));

        // 删除成员关系
        $userGroup->details()->detach();

        // 删除工作组
        $userGroup->delete();

        return response_success();
    }
}
