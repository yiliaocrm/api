<?php

namespace App\Http\Controllers\Web;

use App\Models\Medium;
use App\Models\ProductType;
use Illuminate\Support\Carbon;
use App\Models\CustomerProduct;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Builder;
use App\Http\Requests\Web\ReportCustomerProductRequest;

class ReportCustomerProductController extends Controller
{
    /**
     * 项目销售排行榜
     * @param ReportCustomerProductRequest $request
     * @return JsonResponse
     */
    public function ranking(ReportCustomerProductRequest $request): JsonResponse
    {
        $rows      = $request->input('rows', 100);
        $sort      = $request->input('sort', 'income');
        $date      = $request->input('created_at');
        $order     = $request->input('order', 'desc');
        $type_id   = $request->input('type_id');
        $medium_id = $request->input('medium_id');

        $builder = CustomerProduct::query()
            ->select([
                'customer_product.product_id',
                'customer_product.product_name',
                'product.type_id'
            ])
            ->selectRaw('SUM(cy_customer_product.times) AS times')
            ->selectRaw('sum(cy_customer_product.income) as income')
            ->selectRaw('sum(cy_customer_product.used) as used')
            ->selectRaw('sum(cy_customer_product.refund_times) as refund_times')
            ->selectRaw('sum(cy_customer_product.leftover) as leftover')
            ->selectRaw('sum(cy_customer_product.payable) as payable')
            ->selectRaw('sum(cy_customer_product.deposit) as deposit')
            ->selectRaw('sum(cy_customer_product.coupon) as coupon')
            ->selectRaw('sum(cy_customer_product.arrearage) as arrearage')
            ->join('product', 'customer_product.product_id', '=', 'product.id')
            ->whereBetween('customer_product.created_at', [
                Carbon::parse($date[0])->startOfDay(),
                Carbon::parse($date[1])->endOfDay()
            ])
            ->when($type_id, fn(Builder $query) => $query->whereIn('product.type_id', ProductType::query()->find($type_id)->getAllChild()->pluck('id')))
            ->when($medium_id, fn(Builder $query) => $query->whereIn('customer_product.medium_id', Medium::query()->find($request->input('medium_id'))->getAllChild()->pluck('id')))
            ->groupBy('customer_product.product_id', 'customer_product.product_name', 'product.type_id');

        $query  = $builder->clone()->orderBy($sort, $order)->paginate($rows);
        $footer = [
            [
                'product_name' => '页小计:',
                'times'        => collect($query->items())->sum('times'),
                'income'       => collect($query->items())->sum('income'),
                'used'         => collect($query->items())->sum('used'),
                'refund_times' => collect($query->items())->sum('refund_times'),
                'leftover'     => collect($query->items())->sum('leftover'),
                'payable'      => collect($query->items())->sum('payable'),
                'deposit'      => collect($query->items())->sum('deposit'),
                'coupon'       => collect($query->items())->sum('coupon'),
                'arrearage'    => collect($query->items())->sum('arrearage'),
            ],
        ];

        // 项目分类
        $query->getCollection()->each(function ($item) {
            $item->type_name = get_tree_name(ProductType::class, $item->type_id, true);
        });

        return response_success([
            'rows'   => $query->items(),
            'total'  => $query->total(),
            'footer' => $footer
        ]);
    }
}
