<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\CashierDetail;
use Carbon\Carbon;
use Illuminate\Http\Request;

class CashierDetailController extends Controller
{
    public function manage(Request $request)
    {
        $rows  = $request->input('rows', 10);
        $sort  = $request->input('sort', 'cashier_detail.created_at');
        $order = $request->input('order', 'desc');
        $query = CashierDetail::select('cashier_detail.*', 'customer.name as customer_name', 'customer.idcard as customer_idcard')
            ->leftJoin('customer', 'customer.id', '=', 'cashier_detail.customer_id')
            ->when($request->input('created_at_start') && $request->input('created_at_end'), function ($query) use ($request) {
                return $query->whereBetween('cashier_detail.created_at', [
                    Carbon::parse($request->input('created_at_start')),
                    Carbon::parse($request->input('created_at_end'))->endOfDay()
                ]);
            })
            ->when($request->input('customer_keyword'), function ($query) use ($request) {
                return $query->where('customer.keyword', 'like', '%' . $request->input('customer_keyword') . '%');
            })
            ->when($request->input('product_name'), function ($query) use ($request) {
                return $query->where(function ($query) use ($request) {
                    $query->where('cashier_detail.product_name', 'like', '%' . $request->input('product_name') . '%')
                        ->orWhere('cashier_detail.goods_name', 'like', '%' . $request->input('product_name') . '%');
                });
            })
            ->when($request->input('package_name'), function ($query) use ($request) {
                return $query->where('cashier_detail.package_name', 'like', '%' . $request->input('product_name') . '%');
            })
            ->when($request->input('cashierable_type'), function ($query) use ($request) {
                return $query->where('cashier_detail.cashierable_type', $request->input('cashierable_type'));
            })
            ->when($request->input('department_id'), function ($query) use ($request) {
                return $query->where('cashier_detail.department_id', $request->input('department_id'));
            })
            ->when($request->input('user_id'), function ($query) use ($request) {
                return $query->where('cashier_detail.user_id', $request->input('user_id'));
            })
            // 收费单号
            ->when($request->input('cashier_id'), function ($query) use ($request) {
                return $query->where('cashier_detail.cashier_id', 'like', '%' . $request->input('cashier_id') . '%');
            })
            ->orderBy($sort, $order)
            ->paginate($rows);

        if ($query) {
            $data['rows']  = $query->items();
            $data['total'] = $query->total();
        } else {
            $data['rows']  = [];
            $data['total'] = 0;
        }

        return response_success($data);
    }
}
