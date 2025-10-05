<?php

namespace App\Http\Controllers\Wechat;

use App\Models\Integral;
use App\Models\CustomerProduct;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;

class MeController extends Controller
{
    /**
     * 获取[我的项目]
     * @param Request $request
     * @return JsonResponse
     */
    public function getProducts(Request $request): JsonResponse
    {
        $rows  = $request->input('rows', 10);
        $sort  = $request->input('sort', 'created_at');
        $order = $request->input('order', 'desc');
        $query = CustomerProduct::query()
            ->where('customer_id', auth('customer')->user()->id)
            ->when($request->input('status'), fn($query) => $query->where('status', $request->input('status')))
            ->orderBy($sort, $order)
            ->paginate($rows);

        return response_success([
            'rows'  => $query->items(),
            'total' => $query->total(),
        ]);
    }

    /**
     * 获取[项目详情]
     * @param Request $request
     * @return JsonResponse
     */
    public function getProductInfo(Request $request): JsonResponse
    {
        $product = CustomerProduct::query()
            ->where('customer_id', auth('customer')->user()->id)
            ->where('id', $request->input('id'))
            ->first();

        return response_success([
            'product' => $product,
            'cashier' => $product->cashier,
        ]);
    }

    /**
     * 获取[现有积分]
     * @return JsonResponse
     */
    public function getTotalIntegral(): JsonResponse
    {
        return response_success([
            'total' => auth('customer')->user()->integral,
        ]);
    }

    /**
     * 获取[积分明细]
     * @param Request $request
     * @return JsonResponse
     */
    public function getIntegrals(Request $request): JsonResponse
    {
        $rows  = $request->input('rows', 10);
        $sort  = $request->input('sort', 'created_at');
        $order = $request->input('order', 'desc');
        $query = Integral::query()
            ->where('customer_id', auth('customer')->user()->id)
            ->when($request->input('type') === 'income', fn($query) => $query->where('integral', '>=', '0'))
            ->when($request->input('type') === 'expense', fn($query) => $query->where('integral', '<', '0'))
            ->orderBy($sort, $order)
            ->paginate($rows);

        return response_success([
            'rows'  => $query->items(),
            'total' => $query->total(),
        ]);
    }
}
