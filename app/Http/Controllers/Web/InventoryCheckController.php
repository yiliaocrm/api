<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\InventoryCheck;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InventoryCheckController extends Controller
{
    public function manage(Request $request): JsonResponse
    {
        $rows  = $request->input('rows', 10);
        $sort  = $request->input('sort', 'inventory_transfer.id');
        $order = $request->input('order', 'desc');
        $data  = [];
        $query = InventoryCheck::query()
            ->orderBy($sort, $order)
            ->paginate($rows);

        if ($query) {
            $data['rows']  = $query->items();
            $data['total'] = $query->total();
        } else {
            $data['rows']  = [];
            $data['total'] = 0;
        }

        return response_success($data);
    }
}
