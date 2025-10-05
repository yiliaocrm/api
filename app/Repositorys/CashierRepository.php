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
     * 预收账款表
     * @param Request $request
     * @return array
     */
    public function cashierDepositReceived(Request $request): array
    {
        $rows   = $request->input('rows', 10);
        $prefix = DB::getTablePrefix();

        // 期初
        $before = CustomerDepositDetail::query()
            ->select('before')
            ->from('customer_deposit_details', 'a')
            ->where('a.customer_id', '=', DB::raw("{$prefix}c.customer_id"))
            ->when($request->input('created_at_start') && $request->input('created_at_end'), function (Builder $query) use ($request) {
                $query->whereBetween('a.created_at', [
                    Carbon::parse($request->input('created_at_start')),
                    Carbon::parse($request->input('created_at_end'))->endOfDay()
                ]);
            })
            ->orderBy('a.id')
            ->limit(1);

        // 期末
        $after = CustomerDepositDetail::query()
            ->select('after')
            ->from('customer_deposit_details', 'b')
            ->where('b.customer_id', '=', DB::raw("{$prefix}c.customer_id"))
            ->when($request->input('created_at_start') && $request->input('created_at_end'), function (Builder $query) use ($request) {
                $query->whereBetween('b.created_at', [
                    Carbon::parse($request->input('created_at_start')),
                    Carbon::parse($request->input('created_at_end'))->endOfDay()
                ]);
            })
            ->orderByDesc('b.id')
            ->limit(1);

        $builder = CustomerDepositDetail::query()
            ->select([
                'customer.name as customer_name',
                'customer.idcard',
            ])
            ->selectRaw("any_value ( {$prefix}c.id ) AS id")
            ->selectSub($before, 'before')
            ->selectSub($after, 'after')
            ->addSelect(DB::raw("SUM( CASE WHEN {$prefix}c.balance > 0 THEN {$prefix}c.balance ELSE 0 END ) AS 'recharge'"))
            ->addSelect(DB::raw("ABS(SUM( CASE WHEN {$prefix}c.balance < 0 AND {$prefix}c.cashierable_type <> 'App\\\\Models\\\\CashierRefund' THEN {$prefix}c.balance ELSE 0 END )) AS 'used'"))
            ->addSelect(DB::raw("ABS(SUM( CASE WHEN {$prefix}c.balance < 0 AND {$prefix}c.cashierable_type = 'App\\\\Models\\\\CashierRefund' THEN {$prefix}c.balance ELSE 0 END )) AS 'refund'"))
            ->from('customer_deposit_details', 'c')
            ->leftJoin('customer', 'customer.id', '=', 'c.customer_id')
            ->when($request->input('created_at_start') && $request->input('created_at_end'), function (Builder $query) use ($request) {
                $query->whereBetween('c.created_at', [
                    Carbon::parse($request->input('created_at_start')),
                    Carbon::parse($request->input('created_at_end'))->endOfDay()
                ]);
            })
            ->when($request->input('keyword'), function (Builder $query) use ($request) {
                $query->where('customer.keyword', 'like', '%' . $request->input('keyword') . '%');
            })
            ->when($request->input('cashierable_type'), function (Builder $query) use ($request) {
                $query->where('c.cashierable_type', $request->input('cashierable_type'));
            })
            ->groupBy('c.customer_id')
            ->orderByDesc('id');

        // 查询
        $query = $builder->clone()->paginate($rows);

        // 合计
        $items = collect($query->items());
        $table = DB::table($builder->clone());

        $footer = [
            [
                'idcard'   => '页小计:',
                'before'   => $items->sum('before'),
                'after'    => $items->sum('after'),
                'used'     => $items->sum('used'),
                'refund'   => $items->sum('refund'),
                'recharge' => $items->sum('recharge'),
            ],
            [
                'idcard'   => '总合计:',
                'before'   => $table->sum('before'),
                'after'    => $table->sum('after'),
                'used'     => $table->sum('used'),
                'refund'   => $table->sum('refund'),
                'recharge' => $table->sum('recharge'),
            ]
        ];

        return [
            'rows'   => $query->items(),
            'total'  => $query->total(),
            'footer' => $footer
        ];
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
