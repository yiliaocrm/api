<?php

namespace App\Http\Controllers\Web;

use Exception;
use Throwable;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\PurchaseReturn;
use App\Exceptions\HisException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Builder;
use App\Http\Requests\PurchaseReturn\CheckRequest;
use App\Http\Requests\PurchaseReturn\CreateRequest;
use App\Http\Requests\PurchaseReturn\RemoveRequest;
use App\Http\Requests\PurchaseReturn\UpdateRequest;

class PurchaseReturnController extends Controller
{
    /**
     * 列表
     * @param Request $request
     * @return JsonResponse
     */
    public function manage(Request $request): JsonResponse
    {
        $sort    = $request->input('sort', 'id');
        $order   = $request->input('order', 'desc');
        $rows    = $request->input('rows', 10);
        $keyword = $request->input('keyword');
        $query   = PurchaseReturn::query()
            ->with([
                'warehouse',
                'details.goodsUnits',
                'details.inventoryBatchs',
                'user:id,name',
                'auditor:id,name',
                'createUser:id,name',
                'checkUserRelation:id,name'
            ])
            ->select("purchase_return.*")
            ->join('purchase_return_detail', 'purchase_return.id', '=', 'purchase_return_detail.purchase_return_id')
            ->when($keyword, fn(Builder $query) => $query->where('purchase_return_detail.goods_name', 'like', "%{$keyword}%"))
            ->queryConditions('PurchaseReturnIndex')
            ->groupBy('purchase_return.id')
            ->orderBy("purchase_return.{$sort}", $order)
            ->paginate($rows);

        return response_success([
            'rows'  => $query->items(),
            'total' => $query->total()
        ]);
    }

    /**
     * 创建退货记录
     * @param CreateRequest $request
     * @return JsonResponse
     * @throws HisException|Throwable
     */
    public function create(CreateRequest $request): JsonResponse
    {
        DB::beginTransaction();

        try {
            // 主表
            $purchaseReturn = PurchaseReturn::query()->create(
                $request->formData()
            );

            // 退货明细
            $purchaseReturn->details()->createMany(
                $request->detailData($purchaseReturn)
            );

            DB::commit();
            return response_success($purchaseReturn);

        } catch (Exception $e) {
            DB::rollBack();
            throw new HisException($e->getMessage());
        }
    }

    /**
     * 更新退货单
     * @param UpdateRequest $request
     * @return JsonResponse
     * @throws HisException|Throwable
     */
    public function update(UpdateRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            $purchaseReturn = PurchaseReturn::query()->find(
                $request->input('id')
            );

            $purchaseReturn->update(
                $request->formData()
            );

            // 删除明细
            $purchaseReturn->details()->delete();

            // 新增明细
            $purchaseReturn->details()->createMany(
                $request->detailData($purchaseReturn)
            );

            DB::commit();
            return response_success($purchaseReturn);
        } catch (Exception $e) {
            DB::rollBack();
            throw new HisException($e->getMessage());
        }
    }

    /**
     * 审核进货退货操作
     * @param CheckRequest $request
     * @return JsonResponse
     * @throws HisException|Throwable
     */
    public function check(CheckRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            $purchaseReturn = PurchaseReturn::query()
                ->lockForUpdate()
                ->find($request->input('id'));

            // 更新主表
            $purchaseReturn->update([
                'status'     => 2,
                'check_user' => user()->id,
                'check_time' => Carbon::now()
            ]);

            // 更新明细表状态
            $purchaseReturn->details()->update([
                'status' => 2
            ]);

            // 更新库存批次表
            $purchaseReturn->details->each(function ($detail) use ($request) {
                $detail->inventoryBatch->update(
                    $request->transformers($detail)
                );
            });

            // 更新库存变动明细表
            $purchaseReturn->inventoryDetail()->createMany(
                $request->inventoryDetailData($purchaseReturn)
            );

            DB::commit();
            return response_success();
        } catch (Exception $e) {
            DB::rollBack();
            throw new HisException($e->getMessage());
        }
    }

    /**
     * 删除进货退货单
     * @param RemoveRequest $request
     * @return JsonResponse
     * @throws HisException|Throwable
     */
    public function remove(RemoveRequest $request)
    {
        DB::beginTransaction();
        try {
            // 查询单据
            $purchaseReturn = PurchaseReturn::query()->find(
                $request->input('id')
            );

            // 删除明细表
            $purchaseReturn->details()->delete();

            // 删除主表
            $purchaseReturn->delete();

            DB::commit();
            return response_success();
        } catch (Exception $e) {
            DB::rollBack();
            throw new HisException($e->getMessage());
        }
    }
}
