<?php

namespace App\Http\Controllers\Web;

use App\Models\ConsumableDetail;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Builder;
use App\Http\Requests\Web\ReportConsumableRequest;

class ReportConsumableController extends Controller
{
    /**
     * 用料登记明细表
     * @param ReportConsumableRequest $request
     * @return JsonResponse
     */
    public function detail(ReportConsumableRequest $request): JsonResponse
    {
        $rows    = $request->input('rows', 100);
        $sort    = $request->input('sort', 'created_at');
        $order   = $request->input('order', 'desc');
        $keyword = $request->input('keyword');

        $builder = ConsumableDetail::query()
            ->with([
                'consumable',
                'warehouse:id,name',
                'department:id,name',
            ])
            ->select([
                'consumable_detail.*',
            ])
            ->leftJoin('goods', 'goods.id', '=', 'consumable_detail.goods_id')
            ->leftJoin('consumable', 'consumable.id', '=', 'consumable_detail.consumable_id')
            ->queryConditions('ReportConsumableDetail')
            ->when($keyword, fn(Builder $query) => $query->where('goods.keyword', 'like', '%' . $keyword . '%'))
            ->orderBy("consumable_detail.{$sort}", $order);

        $query  = $builder->clone()->paginate($rows);
        $footer = [
            [
                'unit_name' => '页小计:',
                'price'     => collect($query->items())->sum('price'),
                'amount'    => collect($query->items())->sum('amount')
            ],
            [
                'unit_name' => '总合计:',
                'price'     => floatval($builder->clone()->sum('consumable_detail.price')),
                'amount'    => floatval($builder->clone()->sum('consumable_detail.amount'))
            ]
        ];

        return response_success([
            'rows'   => $query->items(),
            'total'  => $query->total(),
            'footer' => $footer
        ]);
    }
}
