<?php

namespace App\Http\Controllers\Web;

use App\Models\Parameter;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\ParameterRequest;

class ParameterController extends Controller
{
    /**
     * 获取系统配置参数
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $parameters = Parameter::query()->get();
        return response_success($parameters);
    }

    /**
     * 更新参数配置
     * @param ParameterRequest $request
     * @return JsonResponse
     */
    public function update(ParameterRequest $request): JsonResponse
    {
        $parameters = $request->input('config');

        foreach ($parameters as $param) {
            $parameter = Parameter::query()->find($param['name']);
            if ($parameter) {
                $parameter->value = $param['value'];
                $parameter->save();
            }
        }

        return response_success();
    }

    /**
     * 获取系统参数配置(后期加入限制)
     * @param ParameterRequest $request
     * @return JsonResponse
     */
    public function info(ParameterRequest $request): JsonResponse
    {
        return response_success(
            parameter($request->input('key'))
        );
    }
}
