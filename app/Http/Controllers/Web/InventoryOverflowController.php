<?php

namespace App\Http\Controllers\Web;

use Exception;
use Throwable;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Exceptions\HisException;
use App\Models\InventoryOverflow;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Builder;
use App\Http\Requests\InventoryOverflow\CheckRequest;
use App\Http\Requests\InventoryOverflow\CreateRequest;
use App\Http\Requests\InventoryOverflow\RemoveRequest;
use App\Http\Requests\InventoryOverflow\UpdateRequest;

class InventoryOverflowController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $sort    = $request->input('sort', 'id');
        $order   = $request->input('order', 'desc');
        $rows    = $request->input('rows', 10);
        $keyword = $request->input('keyword');
        $query   = InventoryOverflow::query()
            ->with([
                'details.goodsUnits',
                'warehouse:id,name',
                'department:id,name',
                'user:id,name',
                'checkUser:id,name',
                'createUser:id,name',
            ])
            ->select([
                'inventory_overflows.*',
            ])
            ->leftJoin('inventory_overflow_details', 'inventory_overflows.id', '=', 'inventory_overflow_details.inventory_overflow_id')
            ->when($keyword, fn(Builder $query) => $query->where('inventory_overflow_details.goods_name', 'like', "%{$keyword}%"))
            ->queryConditions('InventoryOverflowIndex')
            ->orderBy("inventory_overflows.{$sort}", $order)
            ->paginate($rows);

        return response_success([
            'rows'  => $query->items(),
            'total' => $query->total(),
        ]);
    }

    /**
     * 报溢单登记
     * @param CreateRequest $request
     * @return JsonResponse
     * @throws HisException|Throwable
     */
    public function create(CreateRequest $request): JsonResponse
    {
        DB::beginTransaction();
        try {
            // 主表
            $overflow = InventoryOverflow::query()->create(
                $request->formData()
            );
            // 明细表
            $overflow->details()->createMany(
                $request->detailData($overflow)
            );
            DB::commit();
            return response_success($overflow);

        } catch (Exception $e) {
            DB::rollBack();
            throw new HisException($e->getMessage());
        }
    }

    /**
     * 更新单据
     * @param UpdateRequest $request
     * @return JsonResponse
     * @throws HisException|Throwable
     */
    public function update(UpdateRequest $request): JsonResponse
    {
        DB::beginTransaction();
        try {
            $overflow = InventoryOverflow::query()->find(
                $request->input('id')
            );

            // 更新主表
            $overflow->update(
                $request->formData()
            );

            // 删除明细
            $overflow->details()->delete();

            // 新增明细
            $overflow->details()->createMany(
                $request->detailData($overflow)
            );

            DB::commit();
            return response_success($overflow);

        } catch (Exception $e) {
            DB::rollBack();
            throw new HisException($e->getMessage());
        }
    }

    /**
     * 审核
     * @param CheckRequest $request
     * @return JsonResponse
     * @throws HisException|Throwable
     */
    public function check(CheckRequest $request): JsonResponse
    {
        DB::beginTransaction();
        try {

            $overflow = InventoryOverflow::query()->find(
                $request->input('id')
            );

            // 更新主表
            $overflow->update([
                'status'     => 2,
                'check_user' => user()->id,
                'check_time' => Carbon::now()
            ]);

            // 更新明细表状态
            $overflow->details()->update([
                'status' => 2
            ]);

            // 创建批次
            foreach ($overflow->details as $detail) {
                $overflow->inventoryBatch()->create(
                    $request->inventoryBatchsData($overflow, $detail)
                );
            }

            // 更新库存变动明细表
            $overflow->inventoryDetail()->createMany(
                $request->inventoryDetailData($overflow)
            );

            DB::commit();
            return response_success($overflow);
        } catch (Exception $e) {
            DB::rollBack();
            throw new HisException($e->getMessage());
        }
    }

    /**
     * 删除单据
     * @param RemoveRequest $request
     * @return JsonResponse
     * @throws Exception
     */
    public function remove(RemoveRequest $request): JsonResponse
    {
        $overflow = InventoryOverflow::query()->find(
            $request->input('id')
        );
        $overflow->details()->delete();
        $overflow->delete();
        return response_success();
    }
}
