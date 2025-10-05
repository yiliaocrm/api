<?php

namespace App\Http\Controllers\Web;

use App\Exceptions\HisException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Consumable\CreateRequest;
use App\Models\Consumable;
use App\Models\CustomerProduct;
use Exception;
use Throwable;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ConsumableController extends Controller
{
    /**
     * 用料登记列表
     * @param Request $request
     * @return JsonResponse
     */
    public function manage(Request $request)
    {
        $sort  = $request->input('sort', 'id');
        $order = $request->input('order', 'desc');
        $rows  = $request->input('rows', 10);
        $query = Consumable::query()
            ->with([
                'customer:id,idcard,name',
                'details.goodsUnits'
            ])
            ->when($request->input('date_at_start') && $request->input('date_at_end'), function (Builder $query) use ($request) {
                $query->whereBetween('created_at', [
                    Carbon::parse($request->input('date_at_start')),
                    Carbon::parse($request->input('date_at_end'))->endOfDay()
                ]);
            })
            ->when($request->input('customer_keyword'), function (Builder $query) use ($request) {
                $query->whereHas('customer', function (Builder $q) use ($request) {
                    $q->where('keyword', 'like', '%' . $request->input('customer_keyword') . '%');
                });
            })
            ->when($request->input('goods_name'), function (Builder $query) use ($request) {
                $query->whereHas('details', function (Builder $q) use ($request) {
                    $q->where('goods_name', 'like', '%' . $request->input('goods_name') . '%');
                });
            })
            ->when($request->input('key'), fn(Builder $query) => $query->where('key', $request->input('key')))
            ->when($request->input('warehouse_id'), fn(Builder $query) => $query->where('warehouse_id', $request->input('warehouse_id')))
            ->when($request->input('department_id'), fn(Builder $query) => $query->where('department_id', $request->input('department_id')))
            ->when($request->input('product_name'), fn(Builder $query) => $query->where('product_name', 'like', '%' . $request->input('product_name') . '%'))
            ->orderBy($sort, $order)
            ->paginate($rows);

        return response_success([
            'rows'  => $query->items(),
            'total' => $query->total(),
        ]);
    }

    /**
     * 用料登记
     * @param CreateRequest $request
     * @return JsonResponse
     * @throws HisException|Throwable
     */
    public function create(CreateRequest $request)
    {
        DB::beginTransaction();
        try {

            // 创建主单
            $consumable = Consumable::query()->create(
                $request->formData()
            );

            // 明细表
            $consumable->details()->createMany(
                $request->detailData($consumable)
            );

            // 更新库存批次数量
            $consumable->details->each(function ($detail) use ($request) {
                $detail->inventoryBatch->update(
                    $request->transformers($detail)
                );
            });

            // 更新库存变动明细表
            $consumable->inventoryDetail()->createMany(
                $request->inventoryDetailData($consumable)
            );

            DB::commit();
            return response_success($consumable);

        } catch (Exception $e) {
            DB::rollBack();
            throw new HisException($e->getMessage());
        }
    }

    /**
     * 查询客户已购项目
     * @param Request $request
     * @return JsonResponse
     */
    public function customerProduct(Request $request)
    {
        $sort  = $request->input('sort', 'id');
        $order = $request->input('order', 'desc');
        $rows  = $request->input('rows', 10);
        $query = CustomerProduct::query()
            ->where('customer_id', $request->input('customer_id'))
            ->orderBy($sort, $order)
            ->paginate($rows);

        return response_success([
            'rows'  => $query->items(),
            'total' => $query->total(),
        ]);
    }
}
