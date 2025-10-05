<?php

namespace App\Http\Controllers\Web;

use App\Models\GoodsType;
use App\Models\Inventory;
use App\Models\InventoryBatchs;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Builder;
use App\Http\Requests\Web\InventoryRequest;

class InventoryController extends Controller
{
    /**
     * 库存查询
     * @param InventoryRequest $request
     * @return JsonResponse
     */
    public function index(InventoryRequest $request): JsonResponse
    {
        $rows    = $request->input('rows', 10);
        $sort    = $request->input('sort', 'id');
        $order   = $request->input('order', 'desc');
        $keyword = $request->input('keyword');
        $query   = Inventory::query()
            ->with([
                'goods.type:id,name',
                'goods.units:id,basic,goods_id,unit_id',
                'goods.units.unit:id,name',
                'warehouse:id,name',
                'goods:id,name,type_id,specs',
            ])
            ->select([
                'inventory.*'
            ])
            ->join('goods', 'goods.id', '=', 'inventory.goods_id')
            ->whereIn('goods.type_id', GoodsType::query()->find($request->input('type_id'))->getAllChild()->pluck('id'))
            ->when($keyword, fn(Builder $query) => $query->where('goods.keyword', 'like', "%{$keyword}%"))
            ->queryConditions('InventoryIndex')
            ->orderBy("inventory.{$sort}", $order)
            ->paginate($rows);

        return response_success([
            'rows'  => $query->items(),
            'total' => $query->total()
        ]);
    }

    /**
     * 查询商品库存批次
     * @param InventoryRequest $request
     * @return JsonResponse
     */
    public function batch(InventoryRequest $request): JsonResponse
    {
        $rows     = $request->input('rows', 10);
        $sort     = $request->input('sort', 'id');
        $order    = $request->input('order', 'desc');
        $goods_id = $request->input('goods_id');
        $query    = InventoryBatchs::query()
            ->with([
                'manufacturer',
                'goods:id,name'
            ])
            ->where('goods_id', $goods_id)
            ->orderBy($sort, $order)
            ->paginate($rows);

        return response_success([
            'rows'  => $query->items(),
            'total' => $query->total()
        ]);
    }
}
