<?php

namespace App\Http\Controllers\Web;

use App\Models\InventoryDetail;
use App\Models\RetailOutboundDetail;
use App\Http\Requests\Web\ReportErpRequest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;

class ReportErpController extends Controller
{
    /**
     * 库存变动明细表
     * @param ReportErpRequest $request
     * @return JsonResponse
     */
    public function inventoryDetail(ReportErpRequest $request): JsonResponse
    {
        $date       = $request->input('date');
        $rows       = $request->input('rows', 100);
        $sort       = $request->input('sort', 'id');
        $order      = $request->input('order', 'desc');
        $goods_name = $request->input('goods_name');

        $query = InventoryDetail::query()
            ->with([
                'warehouse:id,name'
            ])
            ->select([
                'inventory_detail.*'
            ])
            ->leftJoin('warehouse', 'warehouse.id', '=', 'inventory_detail.warehouse_id')
            ->leftJoin('manufacturer', 'manufacturer.id', '=', 'inventory_detail.manufacturer_id')
            ->whereBetween('inventory_detail.date', [
                $date[0],
                $date[1],
            ])
            ->when($goods_name, fn(Builder $query) => $query->where('inventory_detail.goods_name', 'like', "%{$goods_name}%"))
            ->queryConditions('ReportInventoryDetail')
            ->orderBy("inventory_detail.{$sort}", $order)
            ->paginate($rows);

        return response_success([
            'rows'  => $query->items(),
            'total' => $query->total()
        ]);
    }

    /**
     * 零售出料明细表
     * @param ReportErpRequest $request
     * @return JsonResponse
     */
    public function retailOutboundDetail(ReportErpRequest $request): JsonResponse
    {
        $date    = $request->input('date');
        $rows    = $request->input('rows', 100);
        $sort    = $request->input('sort', 'created_at');
        $order   = $request->input('order', 'desc');
        $keyword = $request->input('keyword');

        $query = RetailOutboundDetail::query()
            ->with([
                'user:id,name',
                'customer:id,name,sex,idcard',
                'warehouse:id,name',
                'department:id,name',
            ])
            ->select([
                'retail_outbound_detail.*'
            ])
            ->leftJoin('customer', 'customer.id', '=', 'retail_outbound_detail.customer_id')
            ->whereBetween('retail_outbound_detail.date', [
                $date[0],
                $date[1],
            ])
            ->when($keyword, fn(Builder $query) => $query->where('customer.keyword', 'like', "%{$keyword}%"))
            ->queryConditions('ReportRetailOutboundDetail')
            ->orderBy("retail_outbound_detail.{$sort}", $order)
            ->paginate($rows);

        return response_success([
            'rows'  => $query->items(),
            'total' => $query->total()
        ]);
    }
}
