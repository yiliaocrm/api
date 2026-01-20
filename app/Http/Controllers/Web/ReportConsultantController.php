<?php

namespace App\Http\Controllers\Web;

use Carbon\Carbon;
use App\Models\ReceptionOrder;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\ReportConsultantRequest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;

class ReportConsultantController extends Controller
{
    /**
     * 现场开单明细表
     * @param ReportConsultantRequest $request
     * @return JsonResponse
     */
    public function order(ReportConsultantRequest $request): JsonResponse
    {
        $rows    = $request->input('rows', 10);
        $sort    = $request->input('sort', 'created_at');
        $order   = $request->input('order', 'desc');
        $keyword = $request->input('keyword');

        $builder = ReceptionOrder::query()
            ->with([
                'user:id,name',
                'customer:id,name,idcard',
                'department:id,name',
                'reception',
                'reception.medium:id,name',
                'reception.department:id,name',
                'reception.consultantUser:id,name',
                'reception.receptionType:id,name',
                'reception.receptionItems',
            ])
            ->select([
                'reception_order.*',
            ])
            ->leftJoin('reception', 'reception.id', '=', 'reception_order.reception_id')
            ->leftJoin('customer', 'customer.id', '=', 'reception_order.customer_id')
            ->queryConditions('ReportConsultantOrder')
            ->whereBetween('reception_order.created_at', [
                Carbon::parse($request->input('created_at.0'))->startOfDay(),
                Carbon::parse($request->input('created_at.1'))->endOfDay()
            ])
            ->when($keyword, fn(Builder $query) => $query->where('customer.keyword', 'like', '%' . $keyword . '%'))
            ->orderBy("reception_order.{$sort}", $order);

        $query = $builder->clone()->paginate($rows);
        $items = collect($query->items());
        $table = $builder->clone();

        $footer = [
            [
                'product_name' => '页小计:',
                'payable'      => $items->sum('payable'),
                'amount'       => $items->sum('amount')
            ],
            [
                'product_name' => '总合计:',
                'payable'      => floatval($table->sum('reception_order.payable')),
                'amount'       => floatval($table->sum('reception_order.amount')),
            ]
        ];

        return response_success([
            'rows'   => $query->items(),
            'total'  => $query->total(),
            'footer' => $footer
        ]);
    }
}
