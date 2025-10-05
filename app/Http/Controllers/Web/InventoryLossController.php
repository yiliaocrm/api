<?php

namespace App\Http\Controllers\Web;

use Exception;
use Throwable;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\InventoryLoss;
use App\Exceptions\HisException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Builder;
use App\Http\Requests\InventoryLoss\CheckRequest;
use App\Http\Requests\InventoryLoss\CreateRequest;
use App\Http\Requests\InventoryLoss\RemoveRequest;
use App\Http\Requests\InventoryLoss\UpdateRequest;

class InventoryLossController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $sort    = $request->input('sort', 'id');
        $order   = $request->input('order', 'desc');
        $rows    = $request->input('rows', 10);
        $keyword = $request->input('keyword');
        $query   = InventoryLoss::query()
            ->with([
                'user:id,name',
                'checkUser:id,name',
                'createUser:id,name',
                'details.goodsUnits',
                'details.inventoryBatchs',
                'warehouse:id,name',
                'department:id,name',
            ])
            ->select([
                'inventory_losses.*',
            ])
            ->leftJoin('inventory_loss_details', 'inventory_losses.id', '=', 'inventory_loss_details.inventory_loss_id')
            ->when($keyword, fn(Builder $query) => $query->where('inventory_loss_details.goods_name', 'like', "%{$keyword}%"))
            ->queryConditions('InventoryLossIndex')
            ->orderBy("inventory_losses.{$sort}", $order)
            ->paginate($rows);

        return response_success([
            'rows'  => $query->items(),
            'total' => $query->total()
        ]);
    }

    /**
     * 创建报损单
     * @param CreateRequest $request
     * @return JsonResponse
     * @throws HisException|Throwable
     */
    public function create(CreateRequest $request): JsonResponse
    {
        DB::beginTransaction();
        try {

            // 主表
            $loss = InventoryLoss::query()->create(
                $request->formData()
            );

            // 明细表
            $loss->details()->createMany(
                $request->detailData($loss)
            );

            DB::commit();
            return response_success($loss);

        } catch (Exception $e) {
            DB::rollBack();
            throw new HisException($e->getMessage());
        }
    }

    /**
     * 更新科室领料单
     * @param UpdateRequest $request
     * @return JsonResponse
     * @throws HisException|Throwable
     */
    public function update(UpdateRequest $request): JsonResponse
    {
        DB::beginTransaction();
        try {
            $loss = InventoryLoss::query()->find(
                $request->input('id')
            );

            $loss->update(
                $request->formData()
            );

            // 删除明细
            $loss->details()->delete();

            // 新增明细
            $loss->details()->createMany(
                $request->detailData($loss)
            );

            DB::commit();
            return response_success($loss);

        } catch (Exception $e) {
            DB::rollBack();
            throw new HisException($e->getMessage());
        }
    }

    /**
     * 审核单据
     * @param CheckRequest $request
     * @return JsonResponse
     * @throws HisException|Throwable
     */
    public function check(CheckRequest $request): JsonResponse
    {
        DB::beginTransaction();
        try {

            $loss = InventoryLoss::query()
                ->lockForUpdate()
                ->find($request->input('id'));

            // 更新主表
            $loss->update([
                'status'     => 2,
                'check_user' => user()->id,
                'check_time' => Carbon::now()
            ]);

            // 更新明细表状态
            $loss->details()->update([
                'status' => 2
            ]);

            // 更新库存批次数量
            $loss->details->each(function ($detail) use ($request) {
                $detail->inventoryBatch->update(
                    $request->transformers($detail)
                );
            });

            // 更新库存变动明细表
            $loss->inventoryDetail()->createMany(
                $request->inventoryDetailData($loss)
            );

            DB::commit();
            return response_success($loss);
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
        $loss = InventoryLoss::query()->find(
            $request->input('id')
        );
        $loss->details()->delete();
        $loss->delete();
        return response_success();
    }
}
