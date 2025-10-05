<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\CouponDetail;
use App\Models\CouponDetailHistory;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class CouponDetailController extends Controller
{
    /**
     * 发券明细
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function manage(Request $request)
    {
        $sort  = $request->input('sort', 'id');
        $order = $request->input('order', 'desc');
        $rows  = $request->input('rows', 10);
        $data  = [];
        $query = CouponDetail::query()
            ->with(['customer:id,name,idcard'])
            ->select('coupon_details.*')
            ->leftJoin('customer', 'customer.id', '=', 'coupon_details.customer_id')
            ->when($request->input('created_at_start') && $request->input('created_at_end'), function (Builder $query) use ($request) {
                $query->whereBetween('coupon_details.created_at', [
                    Carbon::parse($request->input('created_at_start')),
                    Carbon::parse($request->input('created_at_end'))->endOfDay()
                ]);
            })
            ->when($request->input('keyword'), function ($query) use ($request) {
                $query->where('customer.keyword', 'like', '%' . $request->input('keyword') . '%');
            })
            ->when($request->input('number'), function ($query) use ($request) {
                $query->where('coupon_details.number', 'like', '%' . $request->input('number') . '%');
            })
            ->when($request->input('create_user_id'), function ($query) use ($request) {
                $query->where('coupon_details.create_user_id', $request->input('create_user_id'));
            })
            ->when($request->input('status'), function ($query) use ($request) {
                $query->where('coupon_details.status', $request->input('status'));
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

    /**
     * 卡券余额变动历史
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function histories(Request $request)
    {
        $sort  = $request->input('sort', 'created_at');
        $order = $request->input('order', 'desc');
        $rows  = $request->input('rows', 10);
        $data  = [];
        $query = CouponDetailHistory::query()
            ->where('coupon_detail_id', $request->input('coupon_detail_id'))
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
