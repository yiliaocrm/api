<?php

namespace App\Http\Controllers\Web;

use Exception;
use Throwable;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\InventoryBatchs;
use App\Models\InventoryTransfer;
use App\Exceptions\HisException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Builder;
use App\Http\Requests\InventoryTransfer\CheckRequest;
use App\Http\Requests\InventoryTransfer\CreateRequest;
use App\Http\Requests\InventoryTransfer\RemoveRequest;
use App\Http\Requests\InventoryTransfer\UpdateRequest;

class InventoryTransferController extends Controller
{
    /**
     * 调拨单记录
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $rows    = $request->input('rows', 10);
        $sort    = $request->input('sort', 'id');
        $order   = $request->input('order', 'desc');
        $keyword = $request->input('keyword');
        $query   = InventoryTransfer::query()
            ->with([
                'details.goodsUnits',
                'inWarehouse:id,name',
                'outWarehouse:id,name',
                'user:id,name',
                'checkUser:id,name',
                'createUser:id,name',
            ])
            ->select([
                'inventory_transfer.*'
            ])
            ->join('inventory_transfer_detail', 'inventory_transfer.id', '=', 'inventory_transfer_detail.inventory_transfer_id')
            ->when($keyword, fn(Builder $query) => $query->where('inventory_transfer_detail.goods_name', 'like', "%{$keyword}%"))
            ->queryConditions('InventoryTransferIndex')
            ->orderBy($sort, $order)
            ->paginate($rows);

        return response_success([
            'rows'  => $query->items(),
            'total' => $query->total()
        ]);
    }

    /**
     * 创建调拨单
     * @param CreateRequest $request
     * @return JsonResponse
     * @throws HisException|Throwable
     */
    public function create(CreateRequest $request): JsonResponse
    {
        DB::beginTransaction();
        try {

            // 创建主单
            $transfer = InventoryTransfer::query()->create(
                $request->formData()
            );

            // 创建明细表
            $transfer->details()->createMany(
                $request->detailData($transfer)
            );

            DB::commit();

            return response_success($transfer);

        } catch (Exception $e) {
            DB::rollBack();
            throw new HisException($e->getMessage());
        }
    }

    /**
     * 更新调拨单
     * @param UpdateRequest $request
     * @return JsonResponse
     * @throws HisException|Throwable
     */
    public function update(UpdateRequest $request): JsonResponse
    {
        DB::beginTransaction();
        try {
            // 查询主单
            $transfer = InventoryTransfer::query()->find(
                $request->input('id')
            );

            // 更新主单
            $transfer->update(
                $request->formData()
            );

            // 删除明细
            $transfer->details()->delete();

            // 创建明细表
            $transfer->details()->createMany(
                $request->detailData($transfer)
            );

            DB::commit();

            return response_success($transfer);

        } catch (Exception $e) {
            DB::rollBack();
            throw new HisException($e->getMessage());
        }
    }

    /**
     * 审核调拨单
     * @param CheckRequest $request
     * @return JsonResponse
     * @throws HisException|Throwable
     */
    public function check(CheckRequest $request): JsonResponse
    {
        DB::beginTransaction();
        try {

            // 查询主单
            $transfer = InventoryTransfer::query()->find(
                $request->input('id')
            );

            // 更新主表
            $transfer->update([
                'status'     => 2,
                'check_user' => user()->id,
                'check_time' => Carbon::now()
            ]);

            // 更新明细表状态
            $transfer->details()->update([
                'status' => 2
            ]);

            foreach ($transfer->details as $detail) {

                // 调拨出库
                $detail->inventoryBatch->update(
                    $request->inventoryBatchTransferOut($detail)
                );

                // 调拨出库,库存变动明细
                $transfer->inventoryDetail()->create(
                    $request->inventoryDetailTransferOut($transfer, $detail)
                );

                // 查询批次信息
                $inventoryBatch = InventoryBatchs::query()
                    ->where('goods_id', $detail->goods_id)
                    ->where('batch_code', $detail->batch_code)
                    ->where('warehouse_id', $detail->in_warehouse_id)
                    ->first();

                // 有批次,增加批次数量
                if ($inventoryBatch) {
                    $inventoryBatch->update(
                        $request->updateInventoryBatch($detail, $inventoryBatch)
                    );
                } else {
                    // 没有批次则创建
                    $inventoryBatch = $transfer->inventoryBatch()->create(
                        $request->inventoryBatchTransferIn($detail)
                    );
                }

                // 调拨入库,库存变动明细
                $transfer->inventoryDetail()->create(
                    $request->inventoryDetailTransferIn($transfer, $detail, $inventoryBatch->id)
                );
            }

            DB::commit();
            return response_success();

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
        InventoryTransfer::query()
            ->find($request->input('id'))
            ->delete();
        return response_success();
    }
}
