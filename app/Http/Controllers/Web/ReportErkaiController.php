<?php

namespace App\Http\Controllers\Web;

use Carbon\Carbon;
use App\Models\ErkaiDetail;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Builder;
use App\Http\Requests\Web\ReportErkaiRequest;

class ReportErkaiController extends Controller
{
    /**
     * 二开明细
     * @param ReportErkaiRequest $request
     * @return JsonResponse
     */
    public function detail(ReportErkaiRequest $request): JsonResponse
    {
        $rows    = $request->input('rows', 10);
        $sort    = $request->input('sort', 'created_at');
        $order   = $request->input('order', 'desc');
        $keyword = $request->input('keyword');

        $builder = ErkaiDetail::query()
            ->with([
                'user:id,name',
                'erkai',
                'erkai.medium:id,name',
                'customer:id,name,idcard',
                'department:id,name',
            ])
            ->select([
                'erkai_detail.*',
            ])
            ->queryConditions('ReportErkaiDetail')
            ->leftJoin('erkai', 'erkai.id', '=', 'erkai_detail.erkai_id')
            ->leftJoin('customer', 'customer.id', '=', 'erkai_detail.customer_id')
            ->where('erkai_detail.status', 3)
            ->whereBetween('erkai_detail.created_at', [
                Carbon::parse($request->input('created_at.0'))->startOfDay(),
                Carbon::parse($request->input('created_at.1'))->endOfDay()
            ])
            ->when($keyword, fn(Builder $query) => $query->where('customer.keyword', 'like', '%' . $keyword . '%'))
            ->orderBy("erkai_detail.{$sort}", $order);

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

        return response_success([
            'rows'   => $query->items(),
            'total'  => $query->total(),
            'footer' => $footer
        ]);
    }
}
