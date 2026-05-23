<?php

namespace App\Http\Controllers\Web;

use App\Exceptions\HisException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Consumable\CreateRequest;
use App\Models\Consumable;
use App\Models\CustomerProduct;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class ConsumableController extends Controller
{
    /**
     * 用料登记列表
     *
     * @return JsonResponse
     */
    public function manage(Request $request)
    {
        $sort = $request->input('sort', 'id');
        $order = $request->input('order', 'desc');
        $rows = $request->input('rows', 10);
        $filters = $request->input('filters', []);
        $dateStart = $request->input('date_start', $request->input('date_at_start'));
        $dateEnd = $request->input('date_end', $request->input('date_at_end'));
        $keyword = $request->input('keyword', $request->input('customer_keyword'));
        $query = Consumable::query()
            ->with([
                'customer:id,idcard,name',
                'details.goodsUnits',
                'details.inventoryBatchs',
                'warehouse:id,name',
                'department:id,name',
                'user:id,name',
                'createUser:id,name',
            ])
            ->select([
                'consumable.*',
            ])
            ->leftJoin('customer', 'customer.id', '=', 'consumable.customer_id')
            ->when($dateStart && $dateEnd, function (Builder $query) use ($dateStart, $dateEnd) {
                $query->whereBetween('consumable.date', [
                    Carbon::parse($dateStart)->toDateString(),
                    Carbon::parse($dateEnd)->toDateString(),
                ]);
            })
            ->when($keyword, fn (Builder $query) => $query->where('customer.keyword', 'like', "%{$keyword}%"))
            ->when($request->input('goods_name'), function (Builder $query) use ($request) {
                $query->whereHas('details', function (Builder $q) use ($request) {
                    $q->where('goods_name', 'like', '%'.$request->input('goods_name').'%');
                });
            })
            ->when($request->input('key'), fn (Builder $query) => $query->where('key', $request->input('key')))
            ->when($request->input('warehouse_id'), fn (Builder $query) => $query->where('warehouse_id', $request->input('warehouse_id')))
            ->when($request->input('department_id'), fn (Builder $query) => $query->where('department_id', $request->input('department_id')))
            ->when($request->input('product_name'), fn (Builder $query) => $query->where('product_name', 'like', '%'.$request->input('product_name').'%'))
            ->queryConditions('ConsumableIndex', $filters)
            ->orderBy(str_contains($sort, '.') ? $sort : "consumable.{$sort}", $order)
            ->paginate($rows);

        return response_success([
            'rows' => $query->items(),
            'total' => $query->total(),
        ]);
    }

    /**
     * 用料登记
     *
     * @return JsonResponse
     *
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
     *
     * @return JsonResponse
     */
    public function customerProduct(Request $request)
    {
        $sort = $request->input('sort', 'id');
        $order = $request->input('order', 'desc');
        $rows = $request->input('rows', 10);
        $query = CustomerProduct::query()
            ->where('customer_id', $request->input('customer_id'))
            ->orderBy($sort, $order)
            ->paginate($rows);

        return response_success([
            'rows' => $query->items(),
            'total' => $query->total(),
        ]);
    }
}
