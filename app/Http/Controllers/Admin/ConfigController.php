<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\JsonResponse;
use App\Models\Admin\AdminParameter;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ConfigRequest;

class ConfigController extends Controller
{
    /**
     * 基础配置
     * @return JsonResponse
     */
    public function load(): JsonResponse
    {
        $config = AdminParameter::query()->get();
        return response_success($config);
    }

    /**
     * 保存配置
     * @param ConfigRequest $request
     * @return JsonResponse
     */
    public function save(ConfigRequest $request): JsonResponse
    {
        $parameters = $request->input('config');

        foreach ($parameters as $param) {
            $parameter = AdminParameter::query()->find($param['name']);
            if ($parameter) {
                $parameter->value = $param['value'];
                $parameter->save();
            }
        }

        // 清除缓存
        cache()->forget('admin_parameters');

        return response_success();
    }
}
