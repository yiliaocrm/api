<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Miniapp\ChangeRequest;
use App\Models\CustomerWechat;
use App\Models\PersonalAccessToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;


class MiniappController extends Controller
{
    /**
     * 用户列表
     * @param Request $request
     * @return JsonResponse
     */
    public function getUserList(Request $request): JsonResponse
    {
        $rows  = $request->input('rows', 10);
        $sort  = "customer_wechats.{$request->input('sort', 'id')}";
        $order = $request->input('order', 'desc');
        $query = CustomerWechat::query()
            ->select([
                'customer.name as customer_name',
                'customer.idcard as customer_idcard',
                'customer_wechats.*',
            ])
            ->leftJoin('customer', 'customer.id', '=', 'customer_wechats.customer_id')
            ->when($request->input('keyword'), fn($query) => $query->where('customer.keyword', 'like', "%{$request->input('keyword')}%"))
            ->when($request->input('nickname'), fn($query) => $query->where('customer_wechats.nickname', 'like', "%{$request->input('nickname')}%"))
            ->when($request->input('phone'), fn($query) => $query->where('customer_wechats.phone', 'like', "%{$request->input('phone')}%"))
            ->orderBy($sort, $order)
            ->paginate($rows);

        return response_success([
            'rows'  => $query->items(),
            'total' => $query->total(),
        ]);
    }

    /**
     * 更改绑定顾客信息
     * @param ChangeRequest $request
     * @return JsonResponse
     */
    public function change(ChangeRequest $request): JsonResponse
    {
        $wechat = CustomerWechat::query()->find(
            $request->input('id')
        );

        // 删掉之前用户的token
        PersonalAccessToken::query()
            ->where('tokenable_id', $wechat->customer_id)
            ->where('tokenable_type', 'App\\Models\\Customer')
            ->delete();

        $wechat->update([
            'customer_id' => $request->input('customer_id'),
        ]);

        $wechat->customerLog()->create([
            'customer_id' => $request->input('customer_id')
        ]);
        return response_success($wechat);
    }
}
