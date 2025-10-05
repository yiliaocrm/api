<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\DepartmentPickingType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DepartmentPickingTypeController extends Controller
{
    /**
     * 类别列表
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $order = $request->input('id', 'desc');
        $sort  = $request->input('sort', 'id');
        $rows  = $request->input('rows', 10);
        $query = DepartmentPickingType::query()
            ->when($request->input('keyword'), fn(Builder $query) => $query->where('keyword', 'like', '%' . $request->input('keyword') . '%'))
            ->orderBy($sort, $order)
            ->paginate($rows);

        return response_success([
            'rows'  => $query->items(),
            'total' => $query->total()
        ]);
    }

    /**
     * 创建类别
     * @param Request $request
     * @return JsonResponse
     */
    public function create(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:department_picking_types,name',
        ], [
            'name.required' => '名称不能为空',
            'name.string'   => '名称必须是字符串',
            'name.max'      => '名称最大长度为255',
        ]);
        $type = DepartmentPickingType::query()->create([
            'name' => $request->input('name'),
        ]);
        return response_success($type);
    }

    /**
     * 更新类别
     * @param Request $request
     * @return JsonResponse
     */
    public function update(Request $request): JsonResponse
    {
        $request->validate([
            'id'   => 'required|integer|exists:department_picking_types,id',
            'name' => 'required|string|max:255|unique:department_picking_types,name,' . $request->input('id'),
        ], [
            'id.required'   => 'ID不能为空',
            'id.integer'    => 'ID必须是整数',
            'id.exists'     => 'ID不存在',
            'name.required' => '名称不能为空',
            'name.string'   => '名称必须是字符串',
            'name.max'      => '名称最大长度为255',
        ]);
        $type = DepartmentPickingType::query()->find(
            $request->input('id')
        );
        $type->update([
            'name' => $request->input('name'),
        ]);
        return response_success($type);
    }

    /**
     * 删除类别
     * @param Request $request
     * @return JsonResponse
     */
    public function remove(Request $request): JsonResponse
    {
        $request->validate([
            'id' => [
                'required',
                'integer',
                'exists:department_picking_types,id',
                function ($attribute, $value, $fail) {
                    if (DB::table('department_picking')->where('type_id', $value)->exists()) {
                        $fail('该类别已被使用，无法删除');
                    }
                }
            ],
        ], [
            'id.required' => 'ID不能为空',
            'id.integer'  => 'ID必须是整数',
            'id.exists'   => 'ID不存在',
        ]);
        DepartmentPickingType::query()->find(
            $request->input('id')
        )->delete();
        return response_success();
    }
}
