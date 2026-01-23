<?php

namespace App\Repositorys;

use App\Models\RetailOutboundDetail;

use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;

/**
 * erp报表仓库
 */
class ErpReportRepository
{
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
