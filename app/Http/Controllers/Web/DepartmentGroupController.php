<?php

namespace App\Http\Controllers\Web;

use App\Models\DepartmentGroup;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\DepartmentGroupRequest;
use Illuminate\Database\Eloquent\Builder;

class DepartmentGroupController extends Controller
{
    /**
     * 部门组列表
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $rows  = $request->input('rows', 10);
        $sort  = $request->input('sort', 'id');
        $order = $request->input('order', 'desc');
        $name  = $request->input('name');

        $query = DepartmentGroup::query()
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
     * 创建部门组
     * @param DepartmentGroupRequest $request
     * @return JsonResponse
     */
    public function create(DepartmentGroupRequest $request): JsonResponse
    {
        $departmentGroup = DepartmentGroup::query()->create(
            $request->formData()
        );

        // 添加成员
        $departmentGroup->details()->sync(
            $request->input('ids')
        );

        return response_success($departmentGroup);
    }

    /**
     * 更新部门组
     * @param DepartmentGroupRequest $request
     * @return JsonResponse
     */
    public function update(DepartmentGroupRequest $request): JsonResponse
    {
        $departmentGroup = DepartmentGroup::query()->find(
            $request->input('id')
        );

        $departmentGroup->update(
            $request->formData()
        );

        // 更新成员
        $departmentGroup->details()->sync(
            $request->input('ids')
        );

        return response_success($departmentGroup);
    }

    /**
     * 删除部门组
     * @param DepartmentGroupRequest $request
     * @return JsonResponse
     */
    public function remove(DepartmentGroupRequest $request): JsonResponse
    {
        $departmentGroup = DepartmentGroup::query()->find($request->input('id'));

        // 删除成员关系
        $departmentGroup->details()->detach();

        // 删除部门组
        $departmentGroup->delete();

        return response_success();
    }
}