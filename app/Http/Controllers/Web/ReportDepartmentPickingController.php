<?php

namespace App\Http\Controllers\Web;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Models\DepartmentPickingDetail;
use App\Http\Requests\Web\ReportDepartmentPickingRequest;

class ReportDepartmentPickingController extends Controller
{
    /**
     * 科室领料明细表
     * @param ReportDepartmentPickingRequest $request
     * @return JsonResponse
     */
    public function detail(ReportDepartmentPickingRequest $request): JsonResponse
    {
        $rows    = $request->input('rows', 100);
        $sort    = $request->input('sort', 'created_at');
        $order   = $request->input('order', 'desc');
        $keyword = $request->input('keyword');
        $builder = DepartmentPickingDetail::query()
            ->with([
                'warehouse:id,name',
                'department:id,name',
                'departmentPicking.type:id,name',
                'departmentPicking.user:id,name',
                'departmentPicking.createUser:id,name',
                'departmentPicking.auditor:id,name',
                'goods.type:id,name',
            ])
            ->select([
                'department_picking_detail.*',
            ])
            ->leftJoin('department_picking', 'department_picking.id', '=', 'department_picking_detail.department_picking_id')
            ->queryConditions('ReportDepartmentPickingDetail')
            ->when($keyword, fn(Builder $query) => $query->whereLike('department_picking_detail.goods_name', "%{$keyword}%"))
            // 审核通过
            ->where('department_picking.status', 2)
            ->orderBy("department_picking_detail.{$sort}", $order);

        $query  = $builder->clone()->paginate($rows);
        $footer = [
            [
                'unit_name' => '页小计:',
                'price'     => collect($query->items())->sum('price'),
                'amount'    => collect($query->items())->sum('amount')
            ],
            [
                'unit_name' => '总合计:',
                'price'     => floatval($builder->clone()->sum('department_picking_detail.price')),
                'amount'    => floatval($builder->clone()->sum('department_picking_detail.amount'))
            ]
        ];

        return response_success([
            'rows'   => $query->items(),
            'total'  => $query->total(),
            'footer' => $footer
        ]);
    }
}
