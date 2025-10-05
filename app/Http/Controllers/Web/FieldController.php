<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\FieldRequest;
use App\Models\Field;
use Illuminate\Http\JsonResponse;

class FieldController extends Controller
{
    /**
     * 保存字段配置
     * @param FieldRequest $request
     * @return JsonResponse
     */
    public function save(FieldRequest $request): JsonResponse
    {
        $fields = Field::query()->updateOrCreate(
            ['user_id' => user()->id, 'page' => $request->input('page')],
            ['config' => $request->input('config')]
        );
        return response_success($fields);
    }

    /**
     * 重置字段配置
     * @param FieldRequest $request
     * @return JsonResponse
     */
    public function reset(FieldRequest $request): JsonResponse
    {
        Field::query()
            ->where('page', $request->input('page'))
            ->where('user_id', user()->id)
            ->delete();
        return response_success();
    }
}
