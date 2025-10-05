<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\OutpatientPrescription;
use App\Models\OutpatientPrescriptionDetail;
use Illuminate\Http\Request;

class PharmacyController extends Controller
{
    /**
     * 处方列表
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
		$rows  = $request->input('rows', 10);
		$sort  = $request->input('sort', 'id');
		$order = $request->input('order', 'desc');
        $data  = [];
        $query = OutpatientPrescription::with(['customer'])
            ->orderBy($sort, $order)
            ->paginate($rows);

        if($query) {
            $data['rows']  = $query->items();
            $data['total'] = $query->total();
        } else {
            $data['rows']  = [];
            $data['total'] = 0;
        }
        return response_success($data);
    }

    /**
     * 显示处方明细
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function detail(Request $request)
    {
        $data = OutpatientPrescriptionDetail::with(['inventoryBatch' => function($query) {
            $query->where('warehouse_id', 1);
        }])
            ->where('outpatient_prescription_id', $request->input('outpatient_prescription_id'))
            ->get();

        return response_success($data);
    }

}
