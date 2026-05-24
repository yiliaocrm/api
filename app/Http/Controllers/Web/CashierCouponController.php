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
        $sort = $request->input('sort', 'cashier_coupons.created_at');
        $order = $request->input('order', 'desc');
        $rows = $request->input('rows', 10);
        $date = $request->input('date', []);
        $start = $request->input('created_at_start', $date[0] ?? null);
        $end = $request->input('created_at_end', $date[1] ?? null);
        $filters = $request->input('filters', []);

        $query = CashierCoupon::query()
            ->with([
                'customer:id,idcard,name',
            ])
            ->select('cashier_coupons.*')
            ->when($start && $end, function (Builder $builder) use ($start, $end) {
                $builder->whereBetween('cashier_coupons.created_at', [
                    Carbon::parse($start),
                    Carbon::parse($end)->endOfDay(),
                ]);
            })
            ->when($request->input('keyword'), function (Builder $builder) use ($request) {
                $builder->leftJoin('customer', 'customer.id', 'cashier_coupons.customer_id')
                    ->where('customer.keyword', 'like', '%'.$request->input('keyword').'%');
            })
            ->queryConditions('CouponCashierIndex', $filters)
            ->orderBy($sort, $order)
            ->paginate($rows);

        return response_success([
            'rows' => $query->items(),
            'total' => $query->total(),
        ]);
    }
}
