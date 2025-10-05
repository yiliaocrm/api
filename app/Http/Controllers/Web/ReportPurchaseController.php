<?php

namespace App\Http\Controllers\Web;

use App\Models\PurchaseDetail;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Builder;
use App\Http\Requests\Web\ReportPurchaseRequest;

class ReportPurchaseController extends Controller
{
    /**
     * 进货入库明细表
     * @param ReportPurchaseRequest $request
     * @return JsonResponse
     */
    public function detail(ReportPurchaseRequest $request): JsonResponse
    {
        $rows    = $request->input('rows', 100);
        $sort    = $request->input('sort', 'created_at');
        $order   = $request->input('order', 'desc');
        $keyword = $request->input('keyword');
        $builder = PurchaseDetail::query()
            ->select([
                'purchase_detail.*'
            ])
            ->with([
                'goods.type',
                'warehouse:id,name',
            ])
            ->join('goods', 'goods.id', '=', 'purchase_detail.goods_id')
            ->when($keyword, fn(Builder $query) => $query->where('goods.keyword', 'like', '%' . $keyword . '%'))
            ->queryConditions('ReportPurchaseDetail')
            // 已审核入库
            ->where('status', 2)
            ->orderBy("purchase_detail.{$sort}", $order);

        $query  = $builder->clone()->paginate($rows);
        $footer = [
            [
                'unit_name' => '页小计:',
                'price'     => collect($query->items())->sum('price'),
                'amount'    => collect($query->items())->sum('amount')
            ],
            [
                'unit_name' => '总合计:',
                'price'     => floatval($builder->clone()->sum('price')),
                'amount'    => floatval($builder->clone()->sum('amount'))
            ]
        ];

        return response_success([
            'rows'   => $query->items(),
            'total'  => $query->total(),
            'footer' => $footer
        ]);
    }
}
