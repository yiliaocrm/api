<?php

namespace App\Http\Controllers\Web;

use App\Models\InventoryDetail;
use App\Models\InventoryBatchs;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Builder;
use App\Http\Requests\Web\InventoryBatchsRequest;

class InventoryBatchsController extends Controller
{
    /**
     * 库存批次管理
     * @param InventoryBatchsRequest $request
     * @return JsonResponse
     */
    public function index(InventoryBatchsRequest $request)
    {
        $rows    = $request->input('rows', 10);
        $sort    = $request->input('sort', 'id');
        $order   = $request->input('order', 'desc');
        $keyword = $request->input('keyword');
        $query   = InventoryBatchs::query()
            ->with([
                'warehouse:id,name',
            ])
            ->select([
                'inventory_batchs.*'
            ])
            ->when($keyword, fn(Builder $query) => $query->where('inventory_batchs.goods_name', 'like', "%{$keyword}%"))
            ->queryConditions('InventoryBatchsIndex')
            ->orderBy("inventory_batchs.{$sort}", $order)
            ->paginate($rows);

        return response_success([
            'rows'  => $query->items(),
            'total' => $query->total()
        ]);
    }

    /**
     * 批次明细
     * @param InventoryBatchsRequest $request
     * @return JsonResponse
     */
    public function detail(InventoryBatchsRequest $request): JsonResponse
    {
        $rows  = $request->input('rows', 10);
        $sort  = $request->input('sort', 'id');
        $order = $request->input('order', 'desc');
        $query = InventoryDetail::query()
            ->with([
                'warehouse:id,name',
                'manufacturer:id,name',
            ])
            ->where('inventory_batchs_id', $request->input('inventory_batchs_id'))
            ->orderBy($sort, $order)
            ->paginate($rows);

        return response_success([
            'rows'  => $query->items(),
            'total' => $query->total()
        ]);
    }
}
