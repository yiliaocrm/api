<?php

namespace App\Http\Controllers\Web;

use Carbon\Carbon;
use App\Models\CashierPay;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\CashierPayRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Database\Eloquent\Builder;

class CashierPayController extends Controller
{
    public function manage(Request $request): JsonResponse
    {
        $sort  = request('sort', 'cashier_pay.created_at');
        $order = request('order', 'desc');
        $rows  = request('rows', 10);

        $query = CashierPay::query()
            ->with('customer')
            ->select('cashier_pay.*')
            ->leftJoin('customer', 'customer.id', '=', 'cashier_pay.customer_id')
            ->when($request->input('created_at_start') && $request->input('created_at_end'), function (Builder $query) use ($request) {
                $query->whereBetween('cashier_pay.created_at', [
                    Carbon::parse($request->input('created_at_start')),
                    Carbon::parse($request->input('created_at_end'))->endOfDay()
                ]);
            })
            ->when($request->input('keyword'), function (Builder $query) use ($request) {
                $query->where('customer.keyword', 'like', '%' . $request->input('keyword') . '%');
            })
            ->when($request->input('cashier_id'), function (Builder $query) use ($request) {
                $query->where('cashier_pay.cashier_id', 'like', '%' . $request->input('cashier_id') . '%');
            })
            ->when($request->input('accounts_id'), function (Builder $query) use ($request) {
                $query->where('cashier_pay.accounts_id', $request->input('accounts_id'));
            })
            ->when($request->input('remark'), function (Builder $query) use ($request) {
                $query->where('cashier_pay.remark', 'like', '%' . $request->input('remark') . '%');
            })
            ->when($request->input('user_id'), function (Builder $query) use ($request) {
                $query->where('cashier_pay.user_id', $request->input('user_id'));
            })
            ->orderBy($sort, $order)
            ->paginate($rows);

        return response_success([
            'rows'  => $query->items(),
            'total' => $query->total(),
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
