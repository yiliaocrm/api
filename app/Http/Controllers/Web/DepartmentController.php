<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\DepartmentRequest;
use App\Models\Department;
use Exception;
use App\Exceptions\HisException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class DepartmentController extends Controller
{
    /**
     * 科室管理
     * @param Request $request
     * @return JsonResponse
     */
    public function manage(Request $request): JsonResponse
    {
        $rows  = $request->input('rows', 10);
        $sort  = $request->input('sort', 'id');
        $order = $request->input('order', 'desc');
        $query = Department::query()
            ->when($request->input('name'), fn(Builder $query) => $query->where('name', 'like', "%{$request->input('name')}%"))
            ->orderBy($sort, $order)
            ->paginate($rows);

        return response_success([
            'rows'  => $query->items(),
            'total' => $query->total()
        ]);
    }

    /**
     * 创建科室
     * @param DepartmentRequest $request
     * @return JsonResponse
     */
    public function create(DepartmentRequest $request): JsonResponse
    {
        $data = Department::query()->create(
            $request->formData()
        );
        return response_success($data);
    }

    /**
     * 更新科室
     * @param DepartmentRequest $request
     * @return JsonResponse
     */
    public function update(DepartmentRequest $request): JsonResponse
    {
        $department = Department::query()->find(
            $request->input('id')
        );
        $department->update(
            $request->formData()
        );
        return response_success($department);
    }

    /**
     * 删除科室
     * @param DepartmentRequest $request
     * @return JsonResponse
     * @throws HisException|Throwable
     */
    public function remove(DepartmentRequest $request): JsonResponse
    {
        DB::beginTransaction();
        try {
            Department::query()->find($request->input('id'))->delete();
            DB::commit();
            return response_success();
        } catch (Exception $e) {
            DB::rollBack();
            throw new HisException($e->getMessage());
        }
    }

    /**
     * 禁用
     * @param Request $request
     * @return JsonResponse
     */
    public function disable(Request $request): JsonResponse
    {
        $department = Department::query()->find(
            $request->input('id')
        );
        $department->update([
            'disabled' => 1
        ]);
        return response_success($department);
    }

    /**
     * 启用
     * @param Request $request
     * @return JsonResponse
     */
    public function enable(Request $request): JsonResponse
    {
        $department = Department::query()->find(
            $request->input('id')
        );
        $department->update([
            'disabled' => 0
        ]);
        return response_success($department);
    }
}
