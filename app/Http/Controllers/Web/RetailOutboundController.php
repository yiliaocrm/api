<?php

namespace App\Http\Controllers\Web;

use App\Exceptions\HisException;
use App\Http\Controllers\Controller;
use App\Http\Requests\RetailOutbound\CreateRequest;
use App\Http\Requests\RetailOutbound\QueryCustomerGoodsRequest;
use App\Models\CustomerGoods;
use App\Models\RetailOutbound;
use Exception;
use Throwable;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RetailOutboundController extends Controller
{
    public function manage(Request $request)
    {
        $sort  = $request->input('sort', 'created_at');
        $order = $request->input('order', 'desc');
        $rows  = $request->input('rows', 10);
        $query = RetailOutbound::query()
            ->with([
                'customer:id,idcard,name',
                'details',
                'user:id,name',
                'createUser:id,name',
            ])
            ->when($request->input('date_start') && $request->input('date_end'), function ($query) use ($request) {
                $query->whereBetween('date', [
                    Carbon::parse($request->input('date_start')),
                    Carbon::parse($request->input('date_end'))->endOfDay()
                ]);
            })
            ->orderBy($sort, $order)
            ->paginate($rows);

        return response_success([
            'rows'  => $query->items(),
            'total' => $query->total()
        ]);
    }

    /**
     * 零售出料
     * @param CreateRequest $request
     * @return JsonResponse
     * @throws HisException|Throwable
     */
    public function create(CreateRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            // 出料主单
            $retailOutbound = RetailOutbound::query()->create(
                $request->formData()
            );

            // 出料明细
            $retailOutbound->details()->createMany(
                $request->detailData($retailOutbound)
            );

            $retailOutbound->details->each(function ($detail) use ($request) {
                // 扣库存批次
                $detail->inventoryBatch->update(
                    $request->inventoryBatchsData($detail)
                );
                // 扣已购物品记录
                $detail->customerGoods->update(
                    $request->customerGoodsData($detail)
                );
            });

            // 写入库存变动明细表
            $retailOutbound->inventoryDetail()->createMany(
                $request->inventoryDetailData($retailOutbound)
            );

            // 计算利润

            // 加载关系
            $retailOutbound->load(['customer:id,idcard,name', 'details']);

            DB::commit();
            return response_success($retailOutbound);
        } catch (Exception $e) {
            DB::rollBack();
            throw new HisException($e->getMessage());
        }
    }

    /**
     * 查询顾客购买物品
     * @param QueryCustomerGoodsRequest $request
     * @return JsonResponse
     */
    public function queryCustomerGoods(QueryCustomerGoodsRequest $request)
    {
        $data = CustomerGoods::query()
            ->with([
                'inventoryBatchs' => function ($query) use ($request) {
                    $query->where('warehouse_id', $request->input('warehouse_id'))
                        ->where('number', '>', 0)
                        ->orderBy('created_at', 'ASC');
                },
                'units'
            ])
            ->where('customer_id', $request->input('customer_id'))
            ->orderBy('created_at', 'desc')
            ->get();
        return response_success($data);
    }
}
