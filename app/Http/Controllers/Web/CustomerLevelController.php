<?php

namespace App\Http\Controllers\Web;

use App\Models\CustomerLevel;
use App\Http\Controllers\Controller;
use App\Http\Requests\CustomerLevel\CreateRequest;
use App\Http\Requests\CustomerLevel\RemoveRequest;
use App\Http\Requests\CustomerLevel\UpdateRequest;
use Illuminate\Http\JsonResponse;

class CustomerLevelController extends Controller
{
    public function manage()
    {
        return CustomerLevel::query()->orderBy('id', 'desc')->get();
    }

    /**
     * 新增会员等级
     * @param CreateRequest $request
     * @return JsonResponse
     */
    public function create(CreateRequest $request)
    {
        $data = CustomerLevel::query()->create(
            $request->input('name')
        );
        return response_success($data);
    }

    /**
     * 更新会员等级
     * @param UpdateRequest $request
     * @return JsonResponse
     */
    public function update(UpdateRequest $request)
    {
        $data = CustomerLevel::query()->find($request->id);
        $data->update([
            'name' => $request->input('name')
        ]);
        return response_success($data);
    }

    /**
     * 删除会员等级
     * @param RemoveRequest $request
     * @return JsonResponse
     */
    public function remove(RemoveRequest $request)
    {
        CustomerLevel::query()->find($request->input('id'))->delete();
        return response_success();
    }
}
