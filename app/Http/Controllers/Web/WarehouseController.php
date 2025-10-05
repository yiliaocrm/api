<?php

namespace App\Http\Controllers\Web;

use App\Exceptions\HisException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\WarehouseRequest;
use App\Models\Warehouse;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class WarehouseController extends Controller
{
    /**
     * 仓库列表
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $rows  = $request->input('rows', 10);
        $sort  = $request->input('sort', 'id');
        $order = $request->input('order', 'desc');
        $query = Warehouse::query()
            ->with([
                'warehouseUsers.user:id,name',
            ])
            ->when($request->input('keyword'), fn(Builder $query) => $query->where('keyword', 'like', '%' . $request->input('keyword') . '%'))
            ->orderBy($sort, $order)
            ->paginate($rows);

        return response_success([
            'rows'  => $query->items(),
            'total' => $query->total(),
        ]);
    }

    /**
     * 创建仓库
     * @param WarehouseRequest $request
     * @return JsonResponse
     * @throws HisException|Throwable
     */
    public function create(WarehouseRequest $request)
    {
        try {

            DB::beginTransaction();

            $warehouse = Warehouse::query()->create(
                $request->formData()
            );

            // 同步仓库负责人
            $warehouse->warehouseUsers()->createMany(
                array_map(fn($user) => ['user_id' => $user], $request->input('users', []))
            );

            // 提交
            DB::commit();
            return response_success($warehouse);

        } catch (Exception $e) {
            DB::rollBack();
            throw new HisException($e->getMessage());
        }
    }

    /**
     * 更新仓库信息
     * @param WarehouseRequest $request
     * @return JsonResponse
     */
    public function update(WarehouseRequest $request)
    {
        $warehouse = Warehouse::query()->find(
            $request->input('id')
        );
        $warehouse->warehouseUsers()->delete();
        $warehouse->warehouseUsers()->createMany(
            array_map(fn($user) => ['user_id' => $user], $request->input('users', []))
        );
        $warehouse->update(
            $request->formData()
        );
        return response_success($warehouse);
    }

    /**
     * 删除仓库
     * @param WarehouseRequest $request
     * @return JsonResponse
     */
    public function remove(WarehouseRequest $request)
    {
        Warehouse::query()->find($request->input('id'))->delete();
        return response_success();
    }

    /**
     * 启用仓库
     * @param WarehouseRequest $request
     * @return JsonResponse
     */
    public function enable(WarehouseRequest $request)
    {
        Warehouse::query()->find($request->get('id'))->update([
            'disabled' => 0
        ]);
        return response_success();
    }

    /**
     * 禁用仓库
     * @param WarehouseRequest $request
     * @return JsonResponse
     */
    public function disable(WarehouseRequest $request)
    {
        Warehouse::query()->find($request->get('id'))->update([
            'disabled' => 1
        ]);
        return response_success();
    }
}
