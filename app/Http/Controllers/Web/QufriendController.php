<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\QufriendRequest;
use App\Models\Qufriend;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QufriendController extends Controller
{
    /**
     * 亲友关系列表
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $rows  = $request->input('rows', 10);
        $sort  = $request->input('sort', 'id');
        $order = $request->input('order', 'desc');
        $query = Qufriend::query()
            ->when($request->input('keyword'), fn(Builder $query) => $query->where('name', 'like', "%{$request->input('keyword')}%"))
            ->orderBy($sort, $order)
            ->paginate($rows);

        return response_success([
            'rows'  => $query->items(),
            'total' => $query->total()
        ]);
    }

    /**
     * 创建亲友关系
     * @param QufriendRequest $request
     * @return JsonResponse
     */
    public function create(QufriendRequest $request): JsonResponse
    {
        $qufriend = Qufriend::query()->create(
            $request->formData()
        );
        return response_success($qufriend);
    }

    /**
     * 更新亲友关系
     * @param QufriendRequest $request
     * @return JsonResponse
     */
    public function update(QufriendRequest $request): JsonResponse
    {
        $qufriend = Qufriend::query()->find(
            $request->input('id')
        );
        $qufriend->update(
            $request->formData()
        );
        return response_success($qufriend);
    }

    /**
     * 删除亲友关系
     * @param QufriendRequest $request
     * @return JsonResponse
     */
    public function remove(QufriendRequest $request): JsonResponse
    {
        Qufriend::query()->find($request->input('id'))->delete();
        return response_success(msg: '删除成功');
    }
}
