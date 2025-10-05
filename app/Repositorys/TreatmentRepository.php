<?php

namespace App\Repositorys;

use Carbon\Carbon;
use App\Models\Treatment;
use Illuminate\Http\Request;

class TreatmentRepository
{
    /**
     * 治疗划扣明细
     * @param Request $request
     * @return array
     */
    public function detail(Request $request): array
    {
        $sort  = $request->input('sort', 'treatment.created_at');
        $order = $request->input('order', 'desc');
        $rows  = $request->input('rows', 10);
        $data  = [];
        $query = Treatment::query()
            ->select('treatment.*', 'customer.name as customer_name', 'customer.idcard as customer_idcard')
            ->leftJoin('customer', 'customer.id', '=', 'treatment.customer_id')
            ->when($request->input('created_at_start') && $request->input('created_at_end'), function ($query) use ($request) {
                $query->whereBetween('treatment.created_at', [
                    Carbon::parse($request->input('created_at_start')),
                    Carbon::parse($request->input('created_at_end'))->endOfDay()
                ]);
            })
            ->when($request->input('status'), function ($query) use ($request) {
                $query->where('status', $request->input('status'));
            })
            ->when($request->input('keyword'), function ($query) use ($request) {
                $query->where('customer.keyword', 'like', '%' . $request->input('keyword') . '%');
            })
            ->when($request->input('product_name'), function ($query) use ($request) {
                $query->where('treatment.product_name', 'like', '%' . $request->input('product_name') . '%');
            })
            ->when($request->input('remark'), function ($query) use ($request) {
                $query->where('treatment.remark', 'like', '%' . $request->input('remark') . '%');
            })
            ->when($request->input('user_id'), function ($query) use ($request) {
                $query->where('treatment.user_id', $request->input('user_id'));
            })
            ->when($request->input('participants'), function ($query) use ($request) {
                $query->leftJoin('treatment_participants', 'treatment.id', '=', 'treatment_participants.treatment_id')
                    ->where('treatment_participants.user_id', $request->input('participants'));
            })
            ->when($request->input('department_id'), function ($query) use ($request) {
                $query->where('treatment.department_id', $request->input('department_id'));
            })
            ->when($request->input('package_name'), function ($query) use ($request) {
                $query->where('treatment.package_name', 'like', '%' . $request->input('package_name') . '%');
            })
            ->orderBy($sort, $order)
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
}
