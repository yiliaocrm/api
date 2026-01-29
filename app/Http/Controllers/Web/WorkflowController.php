<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\WorkflowRequest;
use App\Models\Workflow;
use App\Models\WorkflowCategory;
use App\Models\WorkflowNode;
use App\Models\WorkflowTemplate;
use App\Models\WorkflowTemplateCategory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;

class WorkflowController extends Controller
{
    /**
     * 获取节点类型列表
     */
    public function nodes(): JsonResponse
    {
        $nodes = WorkflowNode::query()
            ->select(['key', 'name', 'icon', 'color', 'description'])
            ->orderBy('id')
            ->get();

        return response_success($nodes);
    }

    /**
     * 工作流分类
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
     */
    public function removeCategory(WorkflowRequest $request): JsonResponse
    {
        WorkflowCategory::query()->find($request->input('id'))->delete();

        return response_success();
    }

    /**
     * 交换分群分类顺序
     */
    public function swapCategory(WorkflowRequest $request): JsonResponse
    {
        $category1 = WorkflowCategory::query()->find(
            $request->input('id1')
        );
        $category2 = WorkflowCategory::query()->find(
            $request->input('id2')
        );
        $update1 = [
            'sort' => $category2->sort,
        ];
        $update2 = [
            'sort' => $category1->sort,
        ];
        $category1->update($update1);
        $category2->update($update2);

        return response_success();
    }

    /**
     * 工作流列表
     */
    public function index(WorkflowRequest $request): JsonResponse
    {
        $rows = $request->input('rows', 10);
        $sort = $request->input('sort', 'created_at');
        $order = $request->input('order', 'desc');
        $query = Workflow::query()
            ->with([
                'category',
                'creator:id,name',
                'customerGroups:id,name',
            ])
            ->when($request->input('name'), fn (Builder $query) => $query->where('name', 'like', '%'.$request->input('name').'%'))
            ->when($request->input('type'), fn (Builder $query) => $query->where('type', $request->input('type')))
            ->when($request->input('status'), fn (Builder $query) => $query->where('status', $request->input('status')))
            ->when($request->input('category_id'), fn (Builder $query) => $query->where('category_id', $request->input('category_id')))
            ->when($request->input('active') !== null, fn (Builder $query) => $query->where('active', $request->input('active')))
            ->orderBy($sort, $order)
            ->paginate($rows);

        return response_success([
            'rows' => $query->items(),
            'total' => $query->total(),
        ]);
    }

    /**
     * 创建工作流
     */
    public function create(WorkflowRequest $request): JsonResponse
    {
        $data = $request->validated();

        // 设置默认值
        $data['create_user_id'] = auth()->id();
        $data['version'] = $data['version'] ?? '1.0.0';
        $data['active'] = false;
        $data['status'] = 'draft';

        // 处理 JSON 字段
        if (isset($data['nodes']) && is_string($data['nodes'])) {
            $data['nodes'] = json_decode($data['nodes'], true);
        }
        if (isset($data['connections']) && is_string($data['connections'])) {
            $data['connections'] = json_decode($data['connections'], true);
        }
        if (isset($data['settings']) && is_string($data['settings'])) {
            $data['settings'] = json_decode($data['settings'], true);
        }
        if (isset($data['tags']) && is_string($data['tags'])) {
            $data['tags'] = json_decode($data['tags'], true);
        }

        // 提取客户群组 ID
        $customerGroupIds = $data['customer_group_ids'] ?? [];
        unset($data['customer_group_ids']);

        $workflow = Workflow::create($data);

        // 同步客户群组
        if (! empty($customerGroupIds)) {
            $workflow->customerGroups()->sync($customerGroupIds);
        }

        return response_success($workflow->load(['category', 'creator', 'customerGroups']));
    }

    /**
     * 更新工作流
     */
    public function update(WorkflowRequest $request): JsonResponse
    {
        $workflow = Workflow::findOrFail($request->input('id'));
        $data = $request->validated();

        // 处理 JSON 字段
        if (isset($data['nodes']) && is_string($data['nodes'])) {
            $data['nodes'] = json_decode($data['nodes'], true);
        }
        if (isset($data['connections']) && is_string($data['connections'])) {
            $data['connections'] = json_decode($data['connections'], true);
        }
        if (isset($data['settings']) && is_string($data['settings'])) {
            $data['settings'] = json_decode($data['settings'], true);
        }
        if (isset($data['tags']) && is_string($data['tags'])) {
            $data['tags'] = json_decode($data['tags'], true);
        }

        // 提取客户群组 ID
        $customerGroupIds = $data['customer_group_ids'] ?? null;
        unset($data['customer_group_ids']);
        unset($data['id']);

        $workflow->update($data);

        // 同步客户群组
        if ($customerGroupIds !== null) {
            $workflow->customerGroups()->sync($customerGroupIds);
        }

        return response_success($workflow->load(['category', 'creator', 'customerGroups']));
    }

    /**
     * 删除工作流
     */
    public function remove(WorkflowRequest $request): JsonResponse
    {
        $workflow = Workflow::findOrFail($request->input('id'));

        // 检查是否可以删除
        if ($workflow->active) {
            return response_error('激活状态的工作流不能删除，请先停用');
        }

        $workflow->delete();

        return response_success(null, '工作流已删除');
    }

    /**
     * 工作流详情
     */
    public function detail(WorkflowRequest $request): JsonResponse
    {
        $workflow = Workflow::with([
            'category',
            'creator:id,name',
            'customerGroups:id,name',
        ])->findOrFail($request->input('id'));

        return response_success($workflow);
    }

    /**
     * 激活工作流
     */
    public function activate(WorkflowRequest $request): JsonResponse
    {
        $workflow = Workflow::findOrFail($request->input('id'));

        // 验证工作流是否可以激活
        if (empty($workflow->nodes)) {
            return response_error('工作流没有配置节点，无法激活');
        }

        $workflow->activate();

        return response_success($workflow, '工作流已激活');
    }

    /**
     * 停用工作流
     */
    public function deactivate(WorkflowRequest $request): JsonResponse
    {
        $workflow = Workflow::findOrFail($request->input('id'));

        $workflow->deactivate();

        return response_success($workflow, '工作流已停用');
    }

    /**
     * 复制工作流
     */
    public function duplicate(WorkflowRequest $request): JsonResponse
    {
        $workflow = Workflow::findOrFail($request->input('id'));

        // 创建副本
        $newWorkflow = $workflow->replicate();
        $newWorkflow->name = $workflow->name.' (副本)';
        $newWorkflow->active = false;
        $newWorkflow->status = 'draft';
        $newWorkflow->n8n_id = null;
        $newWorkflow->create_user_id = auth()->id();
        $newWorkflow->save();

        // 复制客户群组关联
        $newWorkflow->customerGroups()->sync($workflow->customerGroups->pluck('id'));

        return response_success($newWorkflow->load(['category', 'creator', 'customerGroups']), '工作流已复制');
    }

    /**
     * 更新节点配置
     */
    public function updateNodes(WorkflowRequest $request): JsonResponse
    {
        $workflow = Workflow::findOrFail($request->input('id'));

        $nodes = $request->input('nodes');
        if (is_string($nodes)) {
            $nodes = json_decode($nodes, true);
        }

        $workflow->update(['nodes' => $nodes]);

        return response_success($workflow, '节点配置已更新');
    }

    /**
     * 更新连接配置
     */
    public function updateConnections(WorkflowRequest $request): JsonResponse
    {
        $workflow = Workflow::findOrFail($request->input('id'));

        $connections = $request->input('connections');
        if (is_string($connections)) {
            $connections = json_decode($connections, true);
        }

        $workflow->update(['connections' => $connections]);

        return response_success($workflow, '连接配置已更新');
    }

    /**
     * 模板分类
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
     */
    public function templateList(WorkflowRequest $request): JsonResponse
    {
        $category_id = $request->input('category_id');
        $templates = WorkflowTemplate::query()
            ->with([
                'category:id,name',
            ])
            ->when($category_id, fn (Builder $query) => $query->where('category_id', $category_id))
            ->get();

        return response_success($templates);
    }
}
