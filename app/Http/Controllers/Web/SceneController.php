<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\SceneRequest;
use App\Models\Scene;
use App\Models\SceneField;
use Illuminate\Http\JsonResponse;

class SceneController extends Controller
{
    /**
     * 加载页面搜索场景列表
     * @param SceneRequest $request
     * @return JsonResponse
     */
    public function lists(SceneRequest $request): JsonResponse
    {
        return response_success(
            Scene::query()
                ->where('page', $request->input('page'))
                ->where(fn($query) => $query->where('public', true)->orWhere('create_user_id', user()->id))
                ->get()
        );
    }

    /**
     * 返回页面搜索字段
     * @param SceneRequest $request
     * @return JsonResponse
     */
    public function fields(SceneRequest $request): JsonResponse
    {
        return response_success(
            SceneField::query()->where('page', $request->input('page'))->get()
        );
    }

    /**
     * 筛选条件标签回显
     * @param SceneRequest $request
     * @return JsonResponse
     */
    public function format(SceneRequest $request): JsonResponse
    {
        $filters = $request->input('filters');

        foreach ($filters as $key => $filter) {
            $filters[$key]['text'] = $request->formatterText($filter);
        }

        return response_success($filters);
    }

    /**
     * 另存为搜索模板
     * @param SceneRequest $request
     * @return JsonResponse
     */
    public function create(SceneRequest $request): JsonResponse
    {
        $scene = Scene::query()->create(
            $request->formData()
        );
        return response_success($scene);
    }

    /**
     * 更新搜索场景
     * @param SceneRequest $request
     * @return JsonResponse
     */
    public function update(SceneRequest $request): JsonResponse
    {
        $scene = Scene::query()->find(
            $request->input('id')
        );
        $scene->update(
            $request->formData()
        );
        return response_success($scene);
    }

    /**
     * 删除搜索场景
     * @param SceneRequest $request
     * @return JsonResponse
     */
    public function remove(SceneRequest $request): JsonResponse
    {
        Scene::query()->find($request->input('id'))->delete();
        return response_success(msg: '删除成功');
    }
}
