<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\CustomerQufriendRequest;
use App\Models\CustomerQufriend;
use Illuminate\Http\JsonResponse;

class CustomerQufriendController extends Controller
{
    /**
     * 回显当前亲友关系详情
     * @param CustomerQufriendRequest $request
     * @return JsonResponse
     */
    public function info(CustomerQufriendRequest $request): JsonResponse
    {
        $qufriend = CustomerQufriend::query()->find(
            $request->input('id')
        );
        $qufriend->load([
            'relatedCustomer:id,idcard,name,sex,consultant,ascription,medium_id',
            'relatedCustomer.consultantUser:id,name',
            'relatedCustomer.ascriptionUser:id,name',
            'relatedCustomer.phones',
            'createUser:id,name',
            'qufriend:id,name',
        ]);
        return response_success($qufriend);
    }

    /**
     * 创建客户亲友关系
     * @param CustomerQufriendRequest $request
     * @return JsonResponse
     */
    public function create(CustomerQufriendRequest $request): JsonResponse
    {
        $data = CustomerQufriend::query()->create(
            $request->formData()
        );
        return response_success($data);
    }

    /**
     * 更新客户亲友关系
     * @param CustomerQufriendRequest $request
     * @return JsonResponse
     */
    public function update(CustomerQufriendRequest $request): JsonResponse
    {
        $data = CustomerQufriend::query()->find(
            $request->input('id')
        );
        $data->update(
            $request->formData()
        );
        return response_success($data);
    }

    /**
     * 删除客户亲友关系
     * @param CustomerQufriendRequest $request
     * @return JsonResponse
     */
    public function remove(CustomerQufriendRequest $request): JsonResponse
    {
        CustomerQufriend::query()->find($request->input('id'))->delete();
        return response_success();
    }
}
