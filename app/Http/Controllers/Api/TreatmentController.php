<?php

namespace App\Http\Controllers\Api;

use App\Models\Treatment;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;

class TreatmentController extends Controller
{

    /**
     * 顾客列表
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $rows  = $request->input('rows', 10);
        $sort  = $request->input('sort', 'created_at');
        $order = $request->input('order', 'desc');
        $query = Treatment::query()
            ->orderBy($sort, $order)
            ->paginate($rows);

        return response_success([
            'rows'  => $query->items(),
            'total' => $query->total()
        ]);
    }
}
