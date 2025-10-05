<?php

namespace App\Http\Controllers\Web;

use App\Models\SmsTemplate;
use App\Models\SmsCategory;
use App\Models\SmsScenario;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Builder;
use App\Http\Requests\Web\SmsTemplateRequest;

class SmsTemplateController extends Controller
{
    /**
     * 短信模板分类列表
     * @return JsonResponse
     */
    public function categories(): JsonResponse
    {
        $categories = SmsCategory::query()
            ->orderBy('sort')
            ->orderBy('id')
            ->get();
        return response_success($categories);
    }

    /**
     * 短信使用场景列表
     * @return JsonResponse
     */
    public function scenarios(): JsonResponse
    {
        $scenarios = SmsScenario::query()
            ->orderBy('id')
            ->get();
        return response_success($scenarios);
    }

    /**
     * 添加短信模板分类
     * @param SmsTemplateRequest $request
     * @return JsonResponse
     */
    public function addCategory(SmsTemplateRequest $request): JsonResponse
    {
        $category = SmsCategory::query()->create([
            'name' => $request->input('name'),
        ]);
        return response_success($category);
    }

    /**
     * 更新短信模板分类
     * @param SmsTemplateRequest $request
     * @return JsonResponse
     */
    public function updateCategory(SmsTemplateRequest $request): JsonResponse
    {
        $category = SmsCategory::query()->find(
            $request->input('id')
        );
        $category->update([
            'name' => $request->input('name'),
        ]);
        return response_success($category);
    }

    /**
     * 删除短信模板分类
     * @param SmsTemplateRequest $request
     * @return JsonResponse
     */
    public function removeCategory(SmsTemplateRequest $request): JsonResponse
    {
        SmsCategory::query()->find($request->input('id'))->delete();
        return response_success();
    }

    /**
     * 交换短信模板分类顺序
     * @param SmsTemplateRequest $request
     * @return JsonResponse
     */
    public function swapCategory(SmsTemplateRequest $request): JsonResponse
    {
        $category1 = SmsCategory::query()->find(
            $request->input('id1')
        );
        $category2 = SmsCategory::query()->find(
            $request->input('id2')
        );
        $update1   = [
            'sort' => $category2->sort,
        ];
        $update2   = [
            'sort' => $category1->sort,
        ];
        $category1->update($update1);
        $category2->update($update2);
        return response_success();
    }

    /**
     * 短信模板列表
     * @param SmsTemplateRequest $request
     * @return JsonResponse
     */
    public function index(SmsTemplateRequest $request): JsonResponse
    {
        $rows        = $request->input('rows', 10);
        $sort        = $request->input('sort', 'created_at');
        $order       = $request->input('order', 'desc');
        $name        = $request->input('name');
        $scenario_id = $request->input('scenario_id');
        $category_id = $request->input('category_id');

        $query = SmsTemplate::query()
            ->with([
                'category:id,name',
                'scenario:id,name'
            ])
            ->when($name, fn(Builder $query) => $query->where('name', 'like', '%' . $name . '%'))
            ->when($scenario_id, fn(Builder $query) => $query->where('scenario_id', $scenario_id))
            ->when($category_id, fn(Builder $query) => $query->where('category_id', $category_id))
            ->orderBy($sort, $order)
            ->paginate($rows);

        return response_success([
            'rows'  => $query->items(),
            'total' => $query->total()
        ]);
    }

    /**
     * 启用短信模板
     * @param SmsTemplateRequest $request
     * @return JsonResponse
     */
    public function enable(SmsTemplateRequest $request): JsonResponse
    {
        $template = SmsTemplate::query()->find(
            $request->input('id')
        );
        $template->update([
            'disabled' => false
        ]);
        return response_success();
    }

    /**
     * 禁用短信模板
     * @param SmsTemplateRequest $request
     * @return JsonResponse
     */
    public function disable(SmsTemplateRequest $request): JsonResponse
    {
        $template = SmsTemplate::query()->find(
            $request->input('id')
        );
        $template->update([
            'disabled' => true
        ]);
        return response_success();
    }

    /**
     * 创建短信模板
     * @param SmsTemplateRequest $request
     * @return JsonResponse
     */
    public function create(SmsTemplateRequest $request): JsonResponse
    {
        $template = SmsTemplate::query()->create(
            $request->formData()
        );
        return response_success($template);
    }

    /**
     * 更新短信模板
     * @param SmsTemplateRequest $request
     * @return JsonResponse
     */
    public function update(SmsTemplateRequest $request): JsonResponse
    {
        $template = SmsTemplate::query()->find(
            $request->input('id')
        );
        $template->update(
            $request->formData()
        );
        return response_success($template);
    }

    /**
     * 删除短信模板
     * @param SmsTemplateRequest $request
     * @return JsonResponse
     */
    public function remove(SmsTemplateRequest $request): JsonResponse
    {
        SmsTemplate::query()->find($request->input('id'))->delete();
        return response_success();
    }
}
