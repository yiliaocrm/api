<?php

namespace App\Http\Controllers\Web;

use Carbon\Carbon;
use App\Models\CashierPay;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\CashierPayRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Database\Eloquent\Builder;

class CashierPayController extends Controller
{
    /**
     * 账户流水列表
     * @param CashierPayRequest $request
     * @return JsonResponse
     */
    public function index(CashierPayRequest $request): JsonResponse
    {
        $sort    = $request->input('sort', 'cashier_pay.created_at');
        $order   = $request->input('order', 'desc');
        $rows    = $request->input('rows', 10);
        $keyword = $request->input('keyword');

        $builder = CashierPay::query()
            ->with([
                'user:id,name',
                'account:id,name',
                'customer:id,idcard,name',
            ])
            ->select('cashier_pay.*')
            ->leftJoin('customer', 'customer.id', '=', 'cashier_pay.customer_id')
            ->whereBetween('cashier_pay.created_at', [
                Carbon::parse($request->input('date.0'))->startOfDay(),
                Carbon::parse($request->input('date.1'))->endOfDay()
            ])
            ->when($keyword, fn(Builder $query) => $query->whereLike('customer.keyword', '%' . $keyword . '%'))
            ->queryConditions('CashierPayIndex')
            ->orderBy($sort, $order);

        $query  = $builder->clone()->paginate($rows);
        $footer = [
            [
                'customer_idcard' => '页小计:',
                'income'          => collect($query->items())->sum('income'),
            ],
            [
                'customer_idcard' => '总合计:',
                'income'          => floatval($builder->clone()->sum('cashier_pay.income')),
            ]
        ];

        return response_success([
            'rows'   => $query->items(),
            'total'  => $query->total(),
            'footer' => $footer
        ]);
    }

    /**
     * 更新账户流水
     * @param CashierPayRequest $request
     * @return JsonResponse
     */
    public function update(CashierPayRequest $request): JsonResponse
    {
        $pay = CashierPay::query()->find(
            $request->input('id')
        );
        $pay->update(
            $request->formData()
        );
        return response_success($pay);
    }
}
