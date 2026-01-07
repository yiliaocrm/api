<?php

namespace App\Http\Controllers\Web;

use Carbon\Carbon;
use App\Models\Treatment;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Builder;
use App\Http\Requests\Web\ReportTreatmentRequest;

class ReportTreatmentController extends Controller
{
    /**
     * 治疗划扣明细
     * @param ReportTreatmentRequest $request
     * @return JsonResponse
     */
    public function detail(ReportTreatmentRequest $request): JsonResponse
    {
        $sort    = $request->input('sort', 'created_at');
        $order   = $request->input('order', 'desc');
        $rows    = $request->input('rows', 10);
        $keyword = $request->input('keyword');

        $query = Treatment::query()
            ->with([
                'user:id,name',
                'department:id,name',
                'treatmentParticipants.user:id,name',
                'product:id,type_id',
                'product.type:id,name',
            ])
            ->select([
                'treatment.*',
                'customer.name as customer_name',
                'customer.idcard as customer_idcard'
            ])
            ->leftJoin('customer', 'customer.id', '=', 'treatment.customer_id')
            ->queryConditions('ReportTreatmentDetail')
            ->whereBetween('treatment.created_at', [
                Carbon::parse($request->input('date.0'))->startOfDay(),
                Carbon::parse($request->input('date.1'))->endOfDay()
            ])
            ->when($keyword, fn(Builder $query) => $query->where('customer.keyword', 'like', '%' . $keyword . '%'))
            ->orderBy("treatment.{$sort}", $order)
            ->paginate($rows);

        $query->append(['status_text']);

        return response_success([
            'rows'  => $query->items(),
            'total' => $query->total()
        ]);
    }
}
