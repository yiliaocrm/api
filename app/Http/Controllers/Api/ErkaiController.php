<?php

namespace App\Http\Controllers\Api;

use App\Models\Erkai;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;

class ErkaiController extends Controller
{

    /**
     * 二开列表
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $rows  = $request->input('rows', 10);
        $sort  = $request->input('sort', 'created_at');
        $order = $request->input('order', 'desc');
        $query = Erkai::query()
            ->with([
                'customer:id,idcard,sex,name',
                'department:id,name',
                'user:id,name'
            ])
            ->orderBy($sort, $order)
            ->paginate($rows);

        return response_success([
            'rows'  => $query->items(),
            'total' => $query->total()
        ]);
    }


}
