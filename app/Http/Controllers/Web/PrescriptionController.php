<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\OutpatientPrescription;
use Illuminate\Http\Request;

class PrescriptionController extends Controller
{
    public function manage(Request $request)
    {
        $data  = [];
        $rows  = $request->input('rows', 10);
        $sort  = $request->input('sort', 'created_at');
        $order = $request->input('order', 'desc');

        $query = OutpatientPrescription::with(['customer', 'details'])
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
}
