<?php

namespace App\Http\Controllers\Web;

use Illuminate\Support\Carbon;
use App\Models\SalesPerformance;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Builder;
use App\Http\Requests\Web\ReportPerformanceRequest;

class ReportPerformanceController extends Controller
{
    /**
     * 职工工作明细表
     * @param ReportPerformanceRequest $request
     * @return JsonResponse
     */
    public function index(ReportPerformanceRequest $request): JsonResponse
    {
        $rows    = $request->input('rows', 10);
        $sort    = $request->input('sort', 'created_at');
        $order   = $request->input('order', 'desc');
        $builder = SalesPerformance::query()
            ->with([
                'user:id,name',
                'customer:id,sex,name,idcard'
            ])
            ->select([
                'sales_performance.*'
            ])
            ->leftJoin('customer', 'customer.id', '=', 'sales_performance.customer_id')
            ->when($keyword = $request->input('keyword'), fn(Builder $query) => $query->where('customer.keyword', 'like', "%{$keyword}%"))
            ->whereBetween('sales_performance.created_at', [
                Carbon::parse($request->input('created_at')[0])->startOfDay(),
                Carbon::parse($request->input('created_at')[1])->endOfDay()
            ])
            ->queryConditions('ReportPerformanceSales')
            // 根据权限过滤
            ->when(!user()->hasAnyAccess(['superuser', 'sales_performance.view.all']), function (Builder $query) {
                $query->whereIn('sales_performance.user_id', user()->getUserIdsForSalesPerformance());
            })
            ->orderBy("sales_performance.{$sort}", $order);;

        $query  = $builder->clone()->paginate($rows);
        $footer = [
            [
                'rate'   => '页小计:',
                'amount' => collect($query->items())->sum('amount'),
            ],
            [
                'rate'   => '总合计:',
                'amount' => floatval($builder->clone()->sum('sales_performance.amount')),
            ]
        ];

        return response_success([
            'rows'   => $query->items(),
            'total'  => $query->total(),
            'footer' => $footer
        ]);
    }
}
