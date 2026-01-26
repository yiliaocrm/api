<?php

namespace App\Http\Controllers\Web;

use App\Models\Workflow;
use App\Models\WorkflowCategory;
use App\Models\WorkflowTemplate;
use App\Models\WorkflowTemplateCategory;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Database\Eloquent\Builder;
use App\Http\Requests\Web\WorkflowRequest;

class WorkflowController extends Controller
{
    /**
     * 工作流分类
     * @return JsonResponse
     */
    public function categories(): JsonResponse
    {
        $categories = WorkflowCategory::query()
            ->orderBy('sort')
            ->orderBy('id')
            ->get();
        return response_success($categories);
    }

    /**
     * 添加分类
     * @param WorkflowRequest $request
     * @return JsonResponse
     */
    public function addCategory(WorkflowRequest $request): JsonResponse
    {
        $category = WorkflowCategory::query()->create([
            'name' => $request->input('name'),
        ]);
        return response_success($category);
    }

    /**
     * 更新分类
     * @param WorkflowRequest $request
     * @return JsonResponse
     */
    public function updateCategory(WorkflowRequest $request): JsonResponse
    {
        $category = WorkflowCategory::query()->findOrFail(
            $request->input('id')
        );
        $category->update([
            'name' => $request->input('name'),
        ]);
        return response_success($category);
    }

    /**
     * 删除分类
     * @param WorkflowRequest $request
     * @return JsonResponse
     */
    public function removeCategory(WorkflowRequest $request): JsonResponse
    {
        WorkflowCategory::query()->find($request->input('id'))->delete();
        return response_success();
    }


    /**
     * 交换分群分类顺序
     * @param WorkflowRequest $request
     * @return JsonResponse
     */
    public function swapCategory(WorkflowRequest $request): JsonResponse
    {
        $category1 = WorkflowCategory::query()->find(
            $request->input('id1')
        );
        $category2 = WorkflowCategory::query()->find(
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
     * 工作流列表
     * @param WorkflowRequest $request
     * @return JsonResponse
     */
    public function index(WorkflowRequest $request): JsonResponse
    {
        $rows  = $request->input('rows', 10);
        $sort  = $request->input('sort', 'created_at');
        $order = $request->input('order', 'desc');
        $query = Workflow::query()
            ->with([
                'category',
                'createUser:id,name',
                'customerGroups:id,name',
            ])
            ->when($request->input('name'), fn(Builder $query) => $query->where('name', 'like', '%' . $request->input('name') . '%'))
            ->when($request->input('type'), fn(Builder $query) => $query->where('type', $request->input('type')))
            ->when($request->input('status'), fn(Builder $query) => $query->where('status', $request->input('status')))
            ->when($request->input('category_id'), fn(Builder $query) => $query->where('category_id', $request->input('category_id')))
            ->orderBy($sort, $order)
            ->paginate($rows);

        return response_success([
            'rows'  => $query->items(),
            'total' => $query->total()
        ]);
    }

    /**
     * 模板分类
     * @return JsonResponse
     */
    public function templateCategory(): JsonResponse
    {
        $category = WorkflowTemplateCategory::query()
            ->withCount('templates')
            ->orderBy('id')
            ->get();
        return response_success($category);
    }

    /**
     * 工作流模板列表
     * @param WorkflowRequest $request
     * @return JsonResponse
     */
    public function templateList(WorkflowRequest $request): JsonResponse
    {
        $category_id = $request->input('category_id');
        $templates   = WorkflowTemplate::query()
            ->with([
                'category:id,name',
            ])
            ->when($category_id, fn(Builder $query) => $query->where('category_id', $category_id))
            ->get();
        return response_success($templates);
    }
}
