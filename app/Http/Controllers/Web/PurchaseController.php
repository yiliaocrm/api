<?php

namespace App\Http\Controllers\Web;

use Exception;
use Throwable;
use Carbon\Carbon;
use App\Models\Purchase;
use App\Exceptions\HisException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Builder;
use App\Http\Requests\Web\PurchaseRequest;
use App\Http\Requests\Purchase\CheckRequest;
use App\Http\Requests\Purchase\CreateRequest;
use App\Http\Requests\Purchase\UpdateRequest;

class PurchaseController extends Controller
{
    /**
     * 进货单列表
     * @param PurchaseRequest $request
     * @return JsonResponse
     */
    public function manage(PurchaseRequest $request): JsonResponse
    {
        $keyword = $request->input('keyword');
        $sort    = $request->input('sort', 'id');
        $rows    = $request->input('rows', 10);
        $order   = $request->input('order', 'desc');
        $query   = Purchase::query()
            ->with([
                'type',
                'warehouse',
                'details.goodsUnits',
                'user:id,name',
                'auditor:id,name',
                'checkUser:id,name',
                'createUser:id,name',
            ])
            ->select("purchase.*")
            ->join('purchase_detail', 'purchase.id', '=', 'purchase_detail.purchase_id')
            ->when($keyword, fn(Builder $query) => $query->where('purchase_detail.goods_name', 'like', "%{$keyword}%"))
            ->queryConditions('PurchaseIndex')
            ->groupBy('purchase.id')
            ->orderBy("purchase.{$sort}", $order)
            ->paginate($rows);

        return response_success([
            'rows'  => $query->items(),
            'total' => $query->total()
        ]);
    }

    /**
     * 进货登记
     * @param CreateRequest $request
     * @return JsonResponse
     * @throws HisException|Throwable
     */
    public function create(CreateRequest $request): JsonResponse
    {
        DB::beginTransaction();
        try {

            // 主表
            $purchase = Purchase::query()->create(
                $request->formData()
            );

            // 明细表
            $purchase->details()->createMany(
                $request->detailData($purchase)
            );

            DB::commit();
            return response_success($purchase);

        } catch (Exception $e) {
            DB::rollBack();
            throw new HisException('进货登记保存失败' . $e->getMessage());
        }
    }

    /**
     * 更新进货单(草稿)
     * @param UpdateRequest $request
     * @return JsonResponse
     * @throws HisException|Throwable
     */
    public function update(UpdateRequest $request): JsonResponse
    {
        DB::beginTransaction();
        try {
            $purchase = Purchase::query()->find(
                $request->input('id')
            );

            // 更新主表
            $purchase->update(
                $request->formData()
            );

            // 删除明细
            $purchase->details()->delete();

            // 新增明细
            $purchase->details()->createMany(
                $request->detailData($purchase)
            );

            DB::commit();
            return response_success($purchase);

        } catch (Exception $e) {
            DB::rollBack();
            throw new HisException('进货单更新失败' . $e->getMessage());
        }
    }

    /**
     * 审核进货单
     * @param CheckRequest $request
     * @return JsonResponse
     * @throws HisException|Throwable
     */
    public function check(CheckRequest $request): JsonResponse
    {
        try {

            DB::beginTransaction();

            // 审核
            $purchase = Purchase::query()->find(
                $request->input('id')
            );

            $purchase->update([
                'status'     => 2,
                'check_user' => user()->id,
                'check_time' => Carbon::now()
            ]);

            // 更新进货明细表状态
            $purchase->details()->update([
                'status' => 2
            ]);

            // 库存批次记录
            $purchase->inventoryBatch()->createMany(
                $request->inventoryBatchData($purchase)
            );

            // 库存变动明细
            $purchase->inventoryDetail()->createMany(
                $request->inventoryDetailData($purchase)
            );

            DB::commit();

            return response_success();

        } catch (Exception $e) {
            DB::rollBack();
            throw new HisException('审核失败' . $e->getMessage());
        }
    }

    /**
     * 删除(未审核)单据
     * @param PurchaseRequest $request
     * @return JsonResponse
     */
    public function remove(PurchaseRequest $request): JsonResponse
    {
        $purchase = Purchase::query()->find($request->input('id'));
        $purchase->details()->delete();
        $purchase->delete();
        return response_success();
    }
}
