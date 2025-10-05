<?php

namespace App\Http\Controllers\Web;

use App\Models\ReceptionType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\ReceptionTypeRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ReceptionTypeController extends Controller
{
    public function manage(Request $request): JsonResponse
    {
        $data = ReceptionType::query()
            ->orderByDesc('id')
            ->get();
        return response_success($data);
    }

    /**
     * 创建分诊接待类型
     * @param ReceptionTypeRequest $request
     * @return JsonResponse
     */
    public function create(ReceptionTypeRequest $request): JsonResponse
    {
        $type = ReceptionType::query()->create(
            $request->only('name', 'remark')
        );
        return response_success($type);
    }

    /**
     * 获取分诊接待类型信息
     * @param ReceptionTypeRequest $request
     * @return JsonResponse
     */
    public function info(ReceptionTypeRequest $request): JsonResponse
    {
        $type = ReceptionType::query()->find(
            $request->input('id')
        );
        return response_success($type);
    }

    /**
     * 更新分诊接待类型
     * @param ReceptionTypeRequest $request
     * @return JsonResponse
     */
    public function update(ReceptionTypeRequest $request): JsonResponse
    {
        $type = ReceptionType::query()->find(
            $request->input('id')
        );
        $type->update(
            $request->only('name', 'remark')
        );
        return response_success($type);
    }

    /**
     * 删除分诊接待类型
     * @param ReceptionTypeRequest $request
     * @return JsonResponse
     */
    public function remove(ReceptionTypeRequest $request): JsonResponse
    {
        ReceptionType::find($request->input('id'))->delete();
        return response_success(msg: '删除成功!');
    }
}
