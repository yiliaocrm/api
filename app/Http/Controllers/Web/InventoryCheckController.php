<?php

namespace App\Http\Controllers\Web;

use App\Exceptions\HisException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\InventoryCheckRequest;
use App\Models\InventoryCheck;
use App\Services\InventoryCheckService;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Throwable;

class InventoryCheckController extends Controller
{
    public function __construct(private readonly InventoryCheckService $inventoryCheckService) {}

    public function manage(InventoryCheckRequest $request): JsonResponse
    {
        $keyword = $request->input('keyword');
        $rows = $request->input('rows', 10);
        $sort = $request->input('sort', 'id');
        $order = strtolower($request->input('order', 'desc')) === 'asc' ? 'asc' : 'desc';
        $sortColumn = str_contains($sort, '.') ? $sort : "inventory_checks.{$sort}";
        $query = InventoryCheck::query()
            ->with([
                'warehouse:id,name',
                'department:id,name',
                'user:id,name',
                'checkUser:id,name',
                'createUser:id,name',
                'inventoryLoss:id,key',
                'inventoryOverflow:id,key',
                'details.goods:id,name',
                'details.inventoryBatch:id,warehouse_id,goods_id,batch_code,production_date,expiry_date,sncode,price,number',
            ])
            ->when($keyword, function (Builder $builder) use ($keyword) {
                $builder->whereExists(function ($subQuery) use ($keyword) {
                    $subQuery->select(DB::raw(1))
                        ->from('inventory_check_details')
                        ->whereColumn('inventory_check_details.inventory_check_id', 'inventory_checks.id')
                        ->where('inventory_check_details.goods_name', 'like', "%{$keyword}%");
                });
            })
            ->queryConditions('InventoryCheckIndex')
            ->orderBy($sortColumn, $order)
            ->paginate($rows);

        return response_success([
            'rows' => $query->items(),
            'total' => $query->total(),
        ]);
    }

    /**
     * 审核盘点单
     *
     * @throws HisException|Throwable
     */
    public function check(InventoryCheckRequest $request): JsonResponse
    {
        $check = $this->inventoryCheckService->approve((int) $request->input('id'));

        return response_success($check);
    }

    /**
     * 盘点草稿登记
     *
     * @throws HisException|Throwable
     */
    public function create(InventoryCheckRequest $request): JsonResponse
    {
        DB::beginTransaction();
        try {
            $check = InventoryCheck::query()->create(
                $request->formData()
            );

            $check->details()->createMany(
                $request->detailData($check)
            );

            DB::commit();

            return response_success($check);
        } catch (Exception $e) {
            DB::rollBack();
            throw new HisException($e->getMessage());
        }
    }

    /**
     * 更新盘点草稿
     *
     * @throws HisException|Throwable
     */
    public function update(InventoryCheckRequest $request): JsonResponse
    {
        DB::beginTransaction();
        try {
            $check = InventoryCheck::query()->find(
                $request->input('id')
            );

            $check->update(
                $request->formData()
            );

            $check->details()->delete();

            $check->details()->createMany(
                $request->detailData($check)
            );

            DB::commit();

            return response_success($check);
        } catch (Exception $e) {
            DB::rollBack();
            throw new HisException($e->getMessage());
        }
    }

    /**
     * 删除盘点草稿
     */
    public function remove(InventoryCheckRequest $request): JsonResponse
    {
        $check = InventoryCheck::query()->find($request->input('id'));
        $check->details()->delete();
        $check->delete();

        return response_success();
    }
}
