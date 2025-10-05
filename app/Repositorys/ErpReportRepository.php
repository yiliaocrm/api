<?php

namespace App\Repositorys;

use App\Models\GoodsType;
use App\Models\InventoryDetail;
use App\Models\RetailOutboundDetail;

use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;

/**
 * erp报表仓库
 */
class ErpReportRepository
{
    /**
     * 库存变动明细表
     * @param Request $request
     * @return array
     */
    public function inventoryDetail(Request $request): array
    {
        $rows  = $request->input('rows', 100);
        $sort  = $request->input('sort', 'id');
        $order = $request->input('order', 'desc');
        $query = InventoryDetail::query()
            ->select('inventory_detail.*')
            ->join('goods', 'goods.id', '=', 'inventory_detail.goods_id')
            // 单据日期
            ->when($request->input('date_start') && $request->input('date_end'), function (Builder $query) use ($request) {
                $query->whereBetween('date', [
                    $request->input('date_start'),
                    $request->input('date_end'),
                ]);
            })
            // 商品名称
            ->when($request->input('goods_name'), function (Builder $query) use ($request) {
                $query->where('goods_name', 'like', '%' . $request->input('goods_name') . '%');
            })
            // 商品类别
            ->when($request->input('goods_type_id'), function (Builder $query) use ($request) {
                $query->whereIn('goods.type_id', GoodsType::query()->find($request->input('goods_type_id'))->getAllChild()->pluck('id'));
            })
            // 业务类型
            ->when($request->input('detailable_type'), function (Builder $query) use ($request) {
                $query->where('detailable_type', $request->input('detailable_type'));
            })
            // 仓库
            ->when($request->input('warehouse_id'), function (Builder $query) use ($request) {
                $query->where('warehouse_id', $request->input('warehouse_id'));
            })
            ->orderBy($sort, $order)
            ->paginate($rows);

        return [
            'rows'  => $query->items(),
            'total' => $query->total()
        ];
    }

    /**
     * 零售出料明细表
     * @param Request $request
     * @return array
     */
    public function retailOutboundDetail(Request $request): array
    {
        $rows  = $request->input('rows', 100);
        $sort  = $request->input('sort', 'created_at');
        $order = $request->input('order', 'desc');
        $query = RetailOutboundDetail::query()
            ->select(['retail_outbound_detail.*', 'customer.name as customer_name', 'customer.idcard as customer_idcard'])
            ->leftJoin('customer', 'customer.id', '=', 'retail_outbound_detail.customer_id')
            ->when($request->input('date_start') && $request->input('date_end'), function (Builder $query) use ($request) {
                $query->whereBetween('retail_outbound_detail.date', [
                    $request->input('date_start'),
                    $request->input('date_end'),
                ]);
            })
            ->when($request->input('key'), function (Builder $query) use ($request) {
                $query->where('retail_outbound_detail.key', 'like', '%' . $request->input('key') . '%');
            })
            ->when($request->input('goods_name'), function (Builder $query) use ($request) {
                $query->where('retail_outbound_detail.goods_name', 'like', '%' . $request->input('goods_name') . '%');
            })
            ->when($request->input('customer_keyword'), function (Builder $query) use ($request) {
                $query->where('customer.keyword', 'like', '%' . $request->input('customer_keyword') . '%');
            })
            ->when($request->input('department_id'), function (Builder $query) use ($request) {
                $query->where('retail_outbound_detail.department_id', $request->input('department_id'));
            })
            ->orderBy($sort, $order)
            ->paginate($rows);

        return [
            'rows'  => $query->items(),
            'total' => $query->total()
        ];
    }
}
