<?php

namespace App\Services;

use Carbon\Carbon;
use App\Models\Customer;
use App\Models\Reception;
use App\Models\CustomerProduct;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;

class CustomerService
{
    /**
     * 自动填充顾客分诊状态
     * @param $customer_id
     * @return array
     */
    public function fill($customer_id): array
    {
        $customer    = Customer::query()->find($customer_id);
        $reception   = $customer->receptions()->orderBy('created_at', 'desc')->first();
        $reservation = $customer->reservations()->whereNull('cometime')->orderBy('created_at', 'desc')->first();
        $data        = [
            'type'       => 1, // 初诊
            'items'      => $customer->items,
            'consultant' => $customer->consultant,
            'medium_id'  => $customer->medium_id,
            'reception'  => user()->id
        ];

        // 网电报单没有上门的
        if ($reservation) {
            $data['department_id'] = $reservation->department_id;
            $data['medium_id']     = $reservation->medium_id;
            $data['items']         = $reservation->items;
        }

        // 最后一次上门记录
        if ($reception) {
            $data['department_id'] = $reception->department_id;
            $data['type']          = 2;
            $data['medium_id']     = $reception->medium_id;

            // 最后一次[分诊记录]是今天
            if ($reception->created_at->startOfDay()->diffInDays(Carbon::now()->startOfDay()) == 0) {
                $data['type'] = $reception->type;
            }
        }

        return $data;
    }

    /**
     * 获取顾客[已购项目]
     * @param Request $request
     * @return array
     */
    public function getCustomerProduct(Request $request): array
    {
        $rows    = $request->input('rows', 10);
        $sort    = $request->input('sort', 'created_at');
        $order   = $request->input('order', 'desc');
        $builder = CustomerProduct::query()
            ->with([
                'medium:id,name',
                'cashier:id,cashierable_type',
            ])
            ->where('customer_id', $request->input('customer_id'))
            ->orderBy($sort, $order);

        $query  = $builder->clone()->paginate($rows);
        $footer = [
            [
                'product_name' => '页小计:',
                'income'       => collect($query->items())->sum('income'),
                'payable'      => collect($query->items())->sum('payable'),
                'arrearage'    => collect($query->items())->sum('arrearage'),
            ],
            [
                'product_name' => '总合计:',
                'income'       => floatval($builder->clone()->sum('income')),
                'payable'      => floatval($builder->clone()->sum('payable')),
                'arrearage'    => floatval($builder->clone()->sum('arrearage')),
            ]
        ];

        return [
            'rows'   => $query->items(),
            'total'  => $query->total(),
            'footer' => $footer
        ];
    }

    /**
     * 获取顾客[上门记录]
     * @param Request $request
     * @return array
     */
    public function getCustomerReception(Request $request): array
    {
        $sort  = $request->input('sort', 'created_at');
        $order = $request->input('order', 'desc');
        $rows  = $request->input('rows', 10);
        $query = Reception::query()
            ->with([
                'receptionItems',
                'medium:id,name',
                'department:id,name',
                'receptionType:id,name',
            ])
            ->where('customer_id', $request->input('customer_id'))
            ->orderBy($sort, $order)
            ->paginate($rows);

        return [
            'rows'  => $query->items(),
            'total' => $query->total()
        ];
    }

    /**
     * 加载顾客列表
     * @param Request $request
     * @return array
     */
    public function getCustomerLists(Request $request): array
    {
        $sort  = $request->input('sort', 'created_at');
        $order = $request->input('order', 'desc');
        $rows  = $request->input('rows', 10);
        $query = Customer::query()
            ->when($request->input('keyword'), fn(Builder $query) => $query->where('keyword', 'like', "%{$request->input('keyword')}%"))
            ->orderBy($sort, $order)
            ->paginate($rows);

        return [
            'rows'  => $query->items(),
            'total' => $query->total()
        ];
    }
}
