<?php

namespace App\Repositorys;

use Carbon\Carbon;
use App\Models\Item;
use App\Models\Medium;
use App\Models\Consultant;
use App\Models\ReceptionOrder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;


class ConsultantRepository
{
    /**
     * 现场咨询明细表
     * @param Request $request
     * @return array
     */
    public function detail(Request $request): array
    {
        $rows  = $request->input('rows', 10);
        $data  = [];
        $query = Consultant::query()
            ->with(['customer:id,name,idcard', 'orders' => function ($query) {
                $query->orderBy('created_at', 'desc');
            }])
            ->select('reception.*')
            ->leftJoin('customer', 'customer.id', '=', 'reception.customer_id')
            // 顾客信息
            ->when($request->input('customer_keyword'), function ($query) use ($request) {
                $query->where('customer.keyword', 'like', '%' . $request->input('customer_keyword') . '%');
            })
            // 咨询日期
            ->when($request->input('created_at_start') && $request->input('created_at_end'), function ($query) use ($request) {
                $query->whereBetween('reception.created_at', [
                    Carbon::parse($request->input('created_at_start')),
                    Carbon::parse($request->input('created_at_end'))->endOfDay()
                ]);
            })
            // 成交状态
            ->when($request->input('status'), function ($query) use ($request) {
                $query->where('reception.status', $request->input('status'));
            })
            // 接诊类型
            ->when($request->input('type'), function ($query) use ($request) {
                $query->where('reception.type', $request->input('type'));
            })
            // 现场咨询
            ->when($request->input('consultant'), function ($query) use ($request) {
                $query->where('reception.consultant', $request->input('consultant'));
            })
            // 咨询科室
            ->when($request->input('department_id'), function ($query) use ($request) {
                $query->where('reception.department_id', $request->input('department_id'));
            })
            // 咨询项目
            ->when($request->input('items'), function ($query) use ($request) {
                $query->leftJoin('reception_items', 'reception.id', '=', 'reception_items.reception_id')
                    ->whereIn('reception_items.item_id', Item::query()->find($request->input('items'))->getAllChild()->pluck('id'));
            })
            // 媒介来源
            ->when($request->input('medium_id'), function ($query) use ($request) {
                $query->whereIn('reception.medium_id', Medium::query()->find($request->input('medium_id'))->getAllChild()->pluck('id'));
            })
            ->orderBy('reception.created_at', 'desc')
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
     * 现场开单明细表
     * @param Request $request
     * @return array
     */
    public function order(Request $request): array
    {
        $rows    = $request->input('rows', 10);
        $builder = ReceptionOrder::query()
            ->select([
                'reception_order.*',
                'reception.type as reception_type',
                'reception.department_id as reception_department_id',
                'reception.items',
                'reception.medium_id',
                'reception.consultant',
                'reception.medium_id'
            ])
            ->with(['customer:id,name,idcard'])
            ->leftJoin('reception', 'reception.id', '=', 'reception_order.reception_id')
            ->when($request->input('created_at_start') && $request->input('created_at_end'), function (Builder $query) use ($request) {
                $query->whereBetween('reception_order.created_at', [
                    Carbon::parse($request->input('created_at_start')),
                    Carbon::parse($request->input('created_at_end'))->endOfDay()
                ]);
            })
            // 顾客信息
            ->when($request->input('keyword'), function (Builder $query) use ($request) {
                $query->leftJoin('customer', 'customer.id', '=', 'reception_order.customer_id')
                    ->where('customer.keyword', 'like', '%' . $request->input('keyword') . '%');
            })
            // 成交状态
            ->when($request->input('status'), function (Builder $query) use ($request) {
                $query->where('reception_order.status', $request->input('status'));
            })
            // 接诊类型
            ->when($request->input('reception_type'), function (Builder $query) use ($request) {
                $query->where('reception.type', $request->input('reception_type'));
            })
            // 现场咨询
            ->when($request->input('consultant'), function (Builder $query) use ($request) {
                $query->where('reception.consultant', $request->input('consultant'));
            })
            // 咨询科室
            ->when($request->input('reception_department_id'), function (Builder $query) use ($request) {
                $query->where('reception.department_id', $request->input('reception_department_id'));
            })
            // 咨询项目
            ->when($request->input('items'), function ($query) use ($request) {
                $query->leftJoin('reception_items', 'reception.id', '=', 'reception_items.reception_id')
                    ->whereIn('reception_items.item_id', Item::query()->find($request->input('items'))->getAllChild()->pluck('id'));
            })
            // 媒介来源
            ->when($request->input('medium_id'), function ($query) use ($request) {
                $query->whereIn('reception.medium_id', Medium::query()->find($request->input('medium_id'))->getAllChild()->pluck('id'));
            });

        // 查询
        $query  = $builder->clone()->orderBy('reception_order.created_at', 'desc')->paginate($rows);
        $footer = [
            [
                'payable' => $builder->clone()->sum('reception_order.payable'),
                'amount'  => $builder->clone()->sum('reception_order.amount')
            ]
        ];

        return [
            'rows'   => $query ? $query->items() : [],
            'total'  => $query ? $query->total() : 0,
            'footer' => $query ? $footer : [],
        ];
    }
}
