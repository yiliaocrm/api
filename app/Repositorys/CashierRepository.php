<?php

namespace App\Repositorys;

use Carbon\Carbon;
use App\Models\Cashier;
use App\Models\CustomerDepositDetail;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Builder;

class CashierRepository
{
    /**
     * 收费明细表
     * @param $request
     * @return array
     */
    public function lists($request): array
    {
        $sort  = $request->input('sort', 'cashier.created_at');
        $order = $request->input('order', 'desc');
        $rows  = $request->input('rows', 10);
        $data  = [];
        $query = Cashier::query()->with('pay')
            ->select('cashier.*', 'customer.name as customer_name', 'customer.idcard as customer_idcard')
            ->leftJoin('customer', 'customer.id', '=', 'cashier.customer_id')
            ->where('status', 2)
            ->when($request->input('created_at_start') && $request->input('created_at_end'), function ($query) use ($request) {
                return $query->whereBetween('cashier.created_at', [
                    Carbon::parse($request->input('created_at_start')),
                    Carbon::parse($request->input('created_at_end'))->endOfDay()
                ]);
            })
            ->when($request->input('customer_keyword'), function ($query) use ($request) {
                return $query->where('customer.keyword', 'like', '%' . $request->input('customer_keyword') . '%');
            })
            ->when($request->input('user_id'), function ($query) use ($request) {
                return $query->where('cashier.user_id', $request->input('user_id'));
            })
            ->when($request->input('id'), function ($query) use ($request) {
                return $query->where(function ($query) use ($request) {
                    $query->where('cashier.id', $request->input('id'))->orWhere('cashier.key', 'like', '%' . $request->input('id') . '%');
                });
            })
            ->when($request->input('updated_at_start') && $request->input('updated_at_end'), function ($query) use ($request) {
                return $query->whereBetween('cashier.updated_at', [
                    Carbon::parse($request->input('updated_at_start')),
                    Carbon::parse($request->input('updated_at_end'))->endOfDay(),
                ]);
            })
            ->when($request->input('operator'), function ($query) use ($request) {
                return $query->where('cashier.operator', $request->input('operator'));
            })
            ->when($request->input('cashierable_type'), function ($query) use ($request) {
                return $query->where('cashier.cashierable_type', $request->input('cashierable_type'));
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

        return $data;
    }

    /**
     * 预收款项变动明细表
     * @param Request $request
     * @return array
     */
    public function cashierDepositDetail(Request $request): array
    {
        $sort  = $request->input('sort', 'customer_deposit_details.created_at');
        $rows  = $request->input('rows', 10);
        $order = $request->input('order', 'desc');
        $query = CustomerDepositDetail::query()
            ->select([
                'customer.name as customer_name',
                'customer.idcard',
                'customer_deposit_details.id',
                'customer_deposit_details.cashier_id',
                'customer_deposit_details.cashierable_type',
                'customer_deposit_details.product_name',
                'customer_deposit_details.goods_name',
                'customer_deposit_details.before',
                'customer_deposit_details.balance',
                'customer_deposit_details.after',
                'customer_deposit_details.created_at',
            ])
            ->leftJoin('customer', 'customer.id', '=', 'customer_deposit_details.customer_id')
            ->when($request->input('created_at_start') && $request->input('created_at_end'), function (Builder $query) use ($request) {
                $query->whereBetween('customer_deposit_details.created_at', [
                    Carbon::parse($request->input('created_at_start')),
                    Carbon::parse($request->input('created_at_end'))->endOfDay()
                ]);
            })
            ->when($request->input('keyword'), function (Builder $query) use ($request) {
                $query->where('customer.keyword', 'like', '%' . $request->input('keyword') . '%');
            })
            ->when($request->input('cashierable_type'), function (Builder $query) use ($request) {
                $query->where('customer_deposit_details.cashierable_type', $request->input('cashierable_type'));
            })
            ->orderBy('customer_deposit_details.id', 'desc')
            ->orderBy($sort, $order)
            ->paginate($rows);

        return [
            'rows'  => $query->items(),
            'total' => $query->total()
        ];
    }
}
