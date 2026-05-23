<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Miniapp\ChangeRequest;
use App\Http\Requests\Miniapp\UserIndexRequest;
use App\Models\CustomerWechat;
use App\Models\PersonalAccessToken;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;

class MiniappController extends Controller
{
    /**
     * 用户列表
     */
    public function getUserList(UserIndexRequest $request): JsonResponse
    {
        $rows = $request->input('rows', 10);
        $sort = "customer_wechats.{$request->input('sort', 'id')}";
        $order = $request->input('order', 'desc');
        $filters = $request->input('filters', []);
        $query = CustomerWechat::query()
            ->select([
                'customer.name as customer_name',
                'customer.idcard as customer_idcard',
                'customer_wechats.*',
            ])
            ->leftJoin('customer', 'customer.id', '=', 'customer_wechats.customer_id')
            ->when($request->input('keyword'), fn ($query) => $query->where('customer.keyword', 'like', "%{$request->input('keyword')}%"))
            ->when($request->input('created_at'), function (Builder $query, array $createdAt): void {
                $query->whereBetween('customer_wechats.created_at', [
                    Carbon::parse($createdAt[0])->startOfDay(),
                    Carbon::parse($createdAt[1])->endOfDay(),
                ]);
            })
            ->queryConditions('MiniappUserIndex', $filters)
            ->orderBy($sort, $order)
            ->paginate($rows);

        return response_success([
            'rows' => $query->items(),
            'total' => $query->total(),
        ]);
    }

    /**
     * 更改绑定顾客信息
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
            'customer_id' => $request->input('customer_id'),
        ]);

        return response_success($wechat);
    }
}
