<?php

namespace App\Repositorys;

use App\Models\CustomerGoods;
use App\Models\CustomerProduct;
use App\Models\CashierRefundDetail;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;

/**
 * 顾客报表
 */
class CustomerReportRepository
{
    /**
     * 顾客消费项目明细表
     * @param Request $request
     * @return array
     */
    public function product(Request $request): array
    {
        $rows    = $request->input('rows', 100);
        $sort    = $request->input('sort', 'created_at');
        $order   = $request->input('order', 'desc');
        $keyword = $request->input('keyword');
        $query   = CustomerProduct::query()
            ->select([
                'customer.sex',
                'customer.name',
                'customer.idcard',
                'customer_product.*'
            ])
            ->with([
                'user:id,name',
                'medium:id,name',
                'department:id,name',
                'doctorUser:id,name',
                'consultantUser:id,name',
                'ekUserRelation:id,name',
                'receptionTypeRelation:id,name',
            ])
            ->leftJoin('customer', 'customer.id', '=', 'customer_product.customer_id')
            ->when($keyword, fn(Builder $query) => $query->where('customer.keyword', 'like', '%' . $keyword . '%'))
            ->queryConditions('ReportCustomerProduct')
            ->orderBy("customer_product.{$sort}", $order)
            ->paginate($rows);

        return [
            'rows'  => $query->items(),
            'total' => $query->total()
        ];
    }

    /**
     * 顾客退款明细表
     * @param Request $request
     * @return array
     */
    public function refund(Request $request): array
    {
        $rows    = $request->input('rows', 100);
        $sort    = $request->input('sort', 'cashier_refund_detail.id');
        $order   = $request->input('order', 'desc');
        $keyword = $request->input('keyword');
        $query   = CashierRefundDetail::query()
            ->with([
                'department:id,name',
            ])
            ->select([
                'customer.sex',
                'customer.age',
                'customer.name as customer_name',
                'customer.idcard as customer_idcard',
                'cashier_refund_detail.*'
            ])
            ->leftJoin('customer', 'customer.id', '=', 'cashier_refund_detail.customer_id')
            ->when($keyword, fn(Builder $query) => $query->where('customer.keyword', 'like', '%' . $keyword . '%'))
            ->queryConditions('ReportCustomerRefund')
            ->orderBy($sort, $order)
            ->paginate($rows);

        return [
            'rows'  => $query->items(),
            'total' => $query->total()
        ];
    }

    /**
     * 顾客物品明细表
     * @param Request $request
     * @return array
     */
    public function goods(Request $request): array
    {
        $rows    = $request->input('rows', 100);
        $sort    = $request->input('sort', 'created_at');
        $order   = $request->input('order', 'desc');
        $keyword = $request->input('keyword');
        $query   = CustomerGoods::query()
            ->select([
                'customer.sex',
                'customer.name',
                'customer.idcard',
                'customer_goods.*'
            ])
            ->with([
                'user:id,name',
                'medium:id,name',
                'department:id,name',
                'doctorUser:id,name',
                'consultantUser:id,name',
                'ekUserRelation:id,name',
                'receptionTypeRelation:id,name',
            ])
            ->leftJoin('customer', 'customer.id', '=', 'customer_goods.customer_id')
            ->queryConditions('ReportCustomerGoods')
            ->when($keyword, fn(Builder $query) => $query->where('customer.keyword', 'like', '%' . $keyword . '%'))
            ->orderBy("customer_goods.{$sort}", $order)
            ->paginate($rows);

        return [
            'rows'  => $query->items(),
            'total' => $query->total()
        ];
    }
}
