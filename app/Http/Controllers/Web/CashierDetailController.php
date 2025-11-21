<?php

namespace App\Http\Controllers\Web;

use Carbon\Carbon;
use App\Models\CashierDetail;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Database\Eloquent\Builder;
use App\Http\Requests\CashierDetailRequest;

class CashierDetailController extends Controller
{
    /**
     * 应收明细列表
     * @param CashierDetailRequest $request
     * @return JsonResponse
     */
    public function index(CashierDetailRequest $request): JsonResponse
    {
        $rows    = $request->input('rows', 10);
        $sort    = $request->input('sort', 'created_at');
        $order   = $request->input('order', 'desc');
        $keyword = $request->input('keyword');
        $builder = CashierDetail::query()
            ->with([
                'user:id,name',
                'unit:id,name',
                'department:id,name',
                'customer:id,name,idcard',
            ])
            ->select(['cashier_detail.*'])
            ->leftJoin('customer', 'customer.id', '=', 'cashier_detail.customer_id')
            ->queryConditions('CashierDetailIndex')
            ->whereBetween('cashier_detail.created_at', [
                Carbon::parse($request->input('date.0'))->startOfDay(),
                Carbon::parse($request->input('date.1'))->endOfDay()
            ])
            ->when($keyword, fn(Builder $query) => $query->whereLike('customer.keyword', '%' . $keyword . '%'))
            ->orderBy("cashier_detail.{$sort}", $order);

        $query  = $builder->clone()->paginate($rows);
        $footer = [
            [
                'specs'     => '页小计:',
                'payable'   => collect($query->items())->sum('payable'),
                'income'    => collect($query->items())->sum('income'),
                'deposit'   => collect($query->items())->sum('deposit'),
                'coupon'    => collect($query->items())->sum('coupon'),
                'arrearage' => collect($query->items())->sum('arrearage'),
            ],
            [
                'specs'     => '总合计:',
                'payable'   => floatval($builder->clone()->sum('cashier_detail.payable')),
                'income'    => floatval($builder->clone()->sum('cashier_detail.income')),
                'deposit'   => floatval($builder->clone()->sum('cashier_detail.deposit')),
                'coupon'    => floatval($builder->clone()->sum('cashier_detail.coupon')),
                'arrearage' => floatval($builder->clone()->sum('cashier_detail.arrearage')),
            ]
        ];

        return response_success([
            'rows'   => $query->items(),
            'total'  => $query->total(),
            'footer' => $footer
        ]);
    }
}
