<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\CashierCoupon;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CashierCouponController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $sort  = $request->input('sort', 'created_at');
        $order = $request->input('order', 'desc');
        $rows  = $request->input('rows', 10);
        $query = CashierCoupon::query()
            ->with([
                'customer:id,idcard,name'
            ])
            ->select('cashier_coupons.*')
            ->when($request->input('created_at_start') && $request->input('created_at_end'), function (Builder $builder) use ($request) {
                $builder->whereBetween('cashier_coupons.created_at', [
                    Carbon::parse($request->input('created_at_start')),
                    Carbon::parse($request->input('created_at_end'))->endOfDay()
                ]);
            })
            ->when($request->input('keyword'), function (Builder $builder) use ($request) {
                $builder->leftJoin('customer', 'customer.id', 'cashier_coupons.customer_id')
                    ->where('customer.keyword', 'like', '%' . $request->input('keyword') . '%');
            })
            ->when($request->input('coupon_number'), function (Builder $builder) use ($request) {
                $builder->where('cashier_coupons.coupon_number', 'like', '%' . $request->input('coupon_number') . '%');
            })
            ->orderBy($sort, $order)
            ->paginate($rows);

        return response_success([
            'rows'  => $query->items(),
            'total' => $query->total()
        ]);
    }
}
