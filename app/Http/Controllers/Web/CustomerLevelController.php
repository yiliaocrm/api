<?php

namespace App\Http\Controllers\Web;

use App\Models\CustomerLevel;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\CustomerLevelRequest;
use Illuminate\Http\JsonResponse;

class CustomerLevelController extends Controller
{
    public function manage()
    {
        return CustomerLevel::query()->orderBy('id', 'desc')->get();
    }

    /**
     * 新增会员等级
     * @param CustomerLevelRequest $request
     * @return JsonResponse
     */
    public function create(CustomerLevelRequest $request)
    {
        $data = CustomerLevel::query()->create(
            $request->input('name')
        );
        return response_success($data);
    }

    /**
     * 更新会员等级
     * @param CustomerLevelRequest $request
     * @return JsonResponse
     */
    public function update(CustomerLevelRequest $request)
    {
        $data = CustomerLevel::query()->find($request->id);
        $data->update([
            'name' => $request->input('name')
        ]);
        return response_success($data);
    }

    /**
     * 删除会员等级
     * @param CustomerLevelRequest $request
     * @return JsonResponse
     */
    public function remove(CustomerLevelRequest $request)
    {
        CustomerLevel::query()->find($request->input('id'))->delete();
        return response_success();
    }
}
