<?php

namespace App\Repositorys;

use Carbon\Carbon;
use App\Models\Medium;
use App\Models\ErkaiDetail;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;

class ErkaiRepository
{

    public function detail(Request $request): array
    {
        $rows    = $request->input('rows', 10);
        $sort    = $request->input('sort', 'erkai_detail.created_at');
        $order   = $request->input('order', 'desc');
        $builder = ErkaiDetail::query()
            ->select([
                'customer.name as customer_name',
                'customer.idcard as customer_idcard',
                'erkai.medium_id',
                'erkai_detail.goods_name',
                'erkai_detail.product_name',
                'erkai_detail.price',
                'erkai_detail.payable',
                'erkai_detail.amount',
                'erkai_detail.coupon',
                'erkai_detail.department_id',
                'erkai_detail.salesman',
                'erkai_detail.user_id',
                'erkai_detail.created_at',
            ])
            ->leftJoin('erkai', 'erkai.id', '=', 'erkai_detail.erkai_id')
            ->leftJoin('customer', 'customer.id', '=', 'erkai_detail.customer_id')
            // 成交状态
            ->where('erkai_detail.status', 3)
            ->when($request->input('created_at_start') && $request->input('created_at_end'), function (Builder $query) use ($request) {
                $query->whereBetween('erkai_detail.created_at', [
                    Carbon::parse($request->input('created_at_start')),
                    Carbon::parse($request->input('created_at_end'))->endOfDay()
                ]);
            })
            ->when($request->input('keyword'), function (Builder $query) use ($request) {
                $query->where('keyword', 'like', '%' . $request->input('keyword') . '%');
            })
            ->when($request->input('name'), function (Builder $query) use ($request) {
                $query->where(function ($query) use ($request) {
                    $query->where('erkai_detail.product_name', 'like', '%' . $request->input('name') . '%')->orWhere('erkai_detail.goods_name', 'like', '%' . $request->input('name') . '%');
                });
            })
            ->when($request->input('department_id'), function (Builder $query) use ($request) {
                $query->where('erkai_detail.department_id', $request->input('department_id'));
            })
            // 媒介来源
            ->when($request->input('medium_id'), function ($query) use ($request) {
                $query->whereIn('erkai.medium_id', Medium::query()->find($request->input('medium_id'))->getAllChild()->pluck('id'));
            })
            ->when($request->input('user_id'), function (Builder $query) use ($request) {
                $query->where('erkai_detail.user_id', $request->input('user_id'));
            })
            ->orderBy($sort, $order);

        $query = $builder->clone()->paginate($rows);
        $items = collect($query->items());
        $table = $builder->clone();

        $footer = [
            [
                'name'    => '页小计:',
                'price'   => $items->sum('price'),
                'payable' => $items->sum('payable'),
                'amount'  => $items->sum('amount'),
                'coupon'  => $items->sum('coupon'),
            ],
            [
                'name'    => '总合计:',
                'price'   => floatval($table->sum('erkai_detail.price')),
                'payable' => floatval($table->sum('erkai_detail.payable')),
                'amount'  => floatval($table->sum('erkai_detail.amount')),
                'coupon'  => floatval($table->sum('erkai_detail.coupon')),
            ]
        ];

        return [
            'rows'   => $query->items(),
            'total'  => $query->total(),
            'footer' => $footer
        ];
    }
}
