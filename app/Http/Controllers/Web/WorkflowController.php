<?php

namespace App\Http\Controllers\Web;

use App\Enums\WorkflowType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\WorkflowRequest;
use App\Models\Workflow;
use App\Models\WorkflowCategory;
use App\Models\WorkflowComponent;
use App\Models\WorkflowComponentType;
use App\Models\WorkflowConditionField;
use App\Models\WorkflowEvent;
use App\Models\WorkflowTemplate;
use App\Models\WorkflowTemplateCategory;
use App\Models\WorkflowVersion;
use App\Services\Workflow\WorkflowPreviewService;
use App\Services\Workflow\WorkflowScheduleSyncService;
use App\Services\Workflow\WorkflowTriggerSampleService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Throwable;

class WorkflowController extends Controller
{
    /**
     * 获取组件类型与组件列表
     */
    public function components(): JsonResponse
    {
        $types = WorkflowComponentType::query()
            ->orderBy('id')
            ->get(['id', 'name', 'key', 'icon', 'bg_color', 'description']);

        $components = WorkflowComponent::query()
            ->select(['key', 'name', 'icon', 'bg_color', 'description', 'output_schema', 'template', 'type_id'])
            ->orderBy('id')
            ->get();

        return response_success([
            'types' => $types,
            'components' => $components,
        ]);
    }

    /**
     * 工作流分类列表
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
     * 新增分类
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
        $category = WorkflowCategory::query()->findOrFail($request->input('id'));
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
        WorkflowCategory::query()->find($request->input('id'))?->delete();

        return response_success();
    }

    /**
     * 交换分类排序
     */
    public function swapCategory(WorkflowRequest $request): JsonResponse
    {
        $category1 = WorkflowCategory::query()->findOrFail($request->input('id1'));
        $category2 = WorkflowCategory::query()->findOrFail($request->input('id2'));

        $category1Sort = $category1->sort;
        $category1->update(['sort' => $category2->sort]);
        $category2->update(['sort' => $category1Sort]);

        return response_success();
    }

    /**
     * 工作流列表
     */
    public function index(WorkflowRequest $request): JsonResponse
    {
        $rows = (int) $request->input('rows', 10);
        $sort = $request->input('sort', 'created_at');
        $order = $request->input('order', 'desc');
        $keyword = (string) ($request->input('keyword') ?? $request->input('name') ?? '');

        $query = Workflow::query()
            ->with([
                'category',
                'creator:id,name',
                'customerGroups:id,name',
            ])
            ->when($keyword !== '', fn (Builder $builder) => $builder->where('name', 'like', "%{$keyword}%"))
            ->when($request->input('type'), fn (Builder $builder) => $builder->where('type', $request->input('type')))
            ->when($request->input('status'), fn (Builder $builder) => $builder->where('status', $request->input('status')))
            ->when($request->input('category_id'), fn (Builder $builder) => $builder->where('category_id', $request->input('category_id')))
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
    public function create(WorkflowRequest $request, WorkflowScheduleSyncService $scheduleSyncService): JsonResponse
    {
        try {
            $workflow = DB::transaction(function () use ($request, $scheduleSyncService) {
                $workflow = Workflow::query()->create($request->fillData());
                $customerGroupIds = $request->customerGroupIds() ?? [];

                if (! empty($customerGroupIds)) {
                    $workflow->customerGroups()->sync($customerGroupIds);
                    $this->backfillWorkflowCustomerGroupTimestamps($workflow->id);
                }

                $scheduleSyncService->sync($workflow);
                $workflow->load('customerGroups:id');
                $request->createVersionSnapshot($workflow, $request->saveSource());

                return $workflow;
            });
        } catch (InvalidArgumentException $exception) {
            return response_error(msg: $exception->getMessage());
        }

        return response_success($workflow->load(['category', 'creator', 'customerGroups']));
    }

    /**
     * 更新工作流
     */
    public function update(WorkflowRequest $request, WorkflowScheduleSyncService $scheduleSyncService): JsonResponse
    {
        try {
            $workflow = DB::transaction(function () use ($request, $scheduleSyncService) {
                $workflow = Workflow::query()->findOrFail($request->input('id'));
                $workflow->update($request->fillData());
                $customerGroupIds = $request->customerGroupIds();

                if ($customerGroupIds !== null) {
                    $workflow->customerGroups()->sync($customerGroupIds);
                    $this->backfillWorkflowCustomerGroupTimestamps($workflow->id);
                }

                $scheduleSyncService->sync($workflow, forceCurrentPeriod: true);
                $workflow->load('customerGroups:id');
                $request->createVersionSnapshot($workflow, $request->saveSource());

                return $workflow;
            });
        } catch (InvalidArgumentException $exception) {
            return response_error(msg: $exception->getMessage());
        }

        return response_success($workflow->load(['category', 'creator', 'customerGroups']));
    }

    /**
     * 删除工作流
     */
    public function remove(WorkflowRequest $request): JsonResponse
    {
        $workflow = Workflow::query()->findOrFail($request->input('id'));

        $workflow->delete();

        return response_success(null, '工作流已删除');
    }

    /**
     * 工作流详情
     */
    public function detail(WorkflowRequest $request): JsonResponse
    {
        $workflow = Workflow::query()->with([
            'category',
            'creator:id,name',
            'customerGroups:id,name',
        ])->findOrFail($request->input('id'));

        return response_success($workflow);
    }

    /**
     * 执行单节点预览并生成输出 schema
     */
    public function previewNode(
        WorkflowRequest $request,
        WorkflowPreviewService $previewService,
        WorkflowTriggerSampleService $triggerSampleService
    ): JsonResponse {
        $ruleChain = $request->input('rule_chain');
        $nodeId = (string) $request->input('node_id');

        $sampleEvent = null;
        $sampleSource = null;
        $inferredInput = [];
        $previewPayload = [];
        try {
            $inferredInput = $previewService->inferPreviewInput($ruleChain, $nodeId);
            $previewPayload = $inferredInput;

            $targetNode = $this->findRuleChainNode($ruleChain, $nodeId);
            $targetNodeType = strtolower((string) ($targetNode['type'] ?? ''));
            if ($targetNodeType === 'start_trigger') {
                $sample = $this->resolveStartTriggerPreviewSample($targetNode, $triggerSampleService);
                if ($sample['event'] !== null) {
                    $sampleEvent = $sample['event'];
                }
                if ($sample['source'] !== null) {
                    $sampleSource = $sample['source'];
                }
                if (! empty($sample['payload'])) {
                    $previewPayload = array_merge($sample['payload'], $previewPayload);
                }
            }

            $preview = $previewService->previewNode($ruleChain, $nodeId, $previewPayload);
        } catch (Throwable $exception) {
            return response_error($exception->getMessage() ?: '执行预览失败');
        }
        $entry = [
            'node_id' => $preview['node_id'],
            'node_type' => $preview['node_type'],
            'node_name' => $preview['node_name'],
            'output_schema' => $preview['output_schema'],
            'output_sample' => $preview['output_data'],
            'input_data' => $previewPayload,
            'upstream_node_ids' => $previewService->findUpstreamNodes($ruleChain, $nodeId),
            'sample_event' => $sampleEvent,
            'sample_source' => $sampleSource,
            'updated_at' => now()->toIso8601String(),
        ];

        $persisted = false;
        $previewSchemas = is_array($ruleChain['meta']['preview_schemas'] ?? null)
            ? $ruleChain['meta']['preview_schemas']
            : [];
        $previewSchemas[$nodeId] = $entry;

        $workflowId = (int) ($request->input('workflow_id') ?? 0);
        if ($workflowId > 0) {
            $workflow = Workflow::query()->find($workflowId);
            if (! $workflow) {
                return response_error('预览失败：工作流不存在');
            }

            $storedRuleChain = is_array($workflow->rule_chain) ? $workflow->rule_chain : [];
            $storedMeta = is_array($storedRuleChain['meta'] ?? null) ? $storedRuleChain['meta'] : [];
            $storedPreviewSchemas = is_array($storedMeta['preview_schemas'] ?? null)
                ? $storedMeta['preview_schemas']
                : [];
            $storedPreviewSchemas[$nodeId] = $entry;
            $storedMeta['preview_schemas'] = $storedPreviewSchemas;
            $storedRuleChain['meta'] = $storedMeta;
            $workflow->rule_chain = $storedRuleChain;
            $workflow->save();

            $previewSchemas = $storedPreviewSchemas;
            $persisted = true;
        }

        return response_success([
            'node_id' => $preview['node_id'],
            'node_type' => $preview['node_type'],
            'node_name' => $preview['node_name'],
            'output_data' => $preview['output_data'],
            'output_schema' => $preview['output_schema'],
            'context_data' => $preview['context_data'],
            'inferred_input' => $previewPayload,
            'sample_event' => $sampleEvent,
            'sample_source' => $sampleSource,
            'preview_schema_entry' => $entry,
            'preview_schemas' => $previewSchemas,
            'persisted' => $persisted,
        ]);
    }

    /**
     * 发布工作流
     */
    public function activate(WorkflowRequest $request, WorkflowScheduleSyncService $scheduleSyncService): JsonResponse
    {
        try {
            $workflow = DB::transaction(function () use ($request, $scheduleSyncService) {
                $workflow = Workflow::query()->findOrFail($request->input('id'));
                $workflow->activate();
                $scheduleSyncService->sync($workflow);

                return $workflow->fresh();
            });
        } catch (InvalidArgumentException $exception) {
            return response_error(msg: $exception->getMessage());
        }

        return response_success($workflow, '工作流已发布');
    }

    /**
     * 取消发布工作流
     */
    public function deactivate(WorkflowRequest $request, WorkflowScheduleSyncService $scheduleSyncService): JsonResponse
    {
        $workflow = DB::transaction(function () use ($request, $scheduleSyncService) {
            $workflow = Workflow::query()->findOrFail($request->input('id'));
            $workflow->deactivate();

            if ($workflow->type === WorkflowType::PERIODIC) {
                $workflow->next_run_at = null;
                $workflow->save();
            } else {
                $scheduleSyncService->sync($workflow);
            }

            return $workflow->fresh();
        });

        return response_success($workflow, '工作流已取消发布');
    }

    /**
     * 历史版本列表
     */
    public function historyList(WorkflowRequest $request): JsonResponse
    {
        $rows = max(1, min((int) $request->input('rows', 20), 200));

        $query = WorkflowVersion::query()
            ->with(['creator:id,name'])
            ->where('workflow_id', $request->input('workflow_id'))
            ->orderByDesc('id')
            ->paginate($rows);

        return response_success([
            'rows' => $query->items(),
            'total' => $query->total(),
        ]);
    }

    /**
     * 历史版本详情
     */
    public function historyDetail(WorkflowRequest $request): JsonResponse
    {
        $version = WorkflowVersion::query()
            ->with(['creator:id,name'])
            ->findOrFail($request->input('id'));

        return response_success($version);
    }

    /**
     * 还原历史版本
     */
    public function historyRestore(WorkflowRequest $request, WorkflowScheduleSyncService $scheduleSyncService): JsonResponse
    {
        $version = WorkflowVersion::query()->findOrFail($request->input('id'));
        $snapshot = is_array($version->snapshot) ? $version->snapshot : [];
        $ruleChain = $snapshot['rule_chain'] ?? null;

        if (! is_array($ruleChain)) {
            return response_error([], '历史版本数据无效：规则链缺失');
        }

        $workflow = Workflow::query()->find($version->workflow_id);
        if (! $workflow) {
            return response_error([], '工作流不存在或已删除');
        }

        $updatedWorkflow = null;
        $restoredVersion = null;

        try {
            DB::transaction(function () use (
                $request,
                $workflow,
                $snapshot,
                $ruleChain,
                $scheduleSyncService,
                &$updatedWorkflow,
                &$restoredVersion
            ) {
                $allCustomer = (bool) ($snapshot['all_customer'] ?? false);
                $customerGroupIds = $this->normalizeCustomerGroupIds($snapshot['customer_group_ids'] ?? []);
                $restoredType = $snapshot['type'] ?? null;
                if (! is_string($restoredType) || $restoredType === '') {
                    $restoredType = $workflow->type instanceof \BackedEnum
                        ? $workflow->type->value
                        : (string) $workflow->type;
                }

                $workflow->update([
                    'name' => (string) ($snapshot['name'] ?? $workflow->name),
                    'description' => $snapshot['description'] ?? null,
                    'category_id' => (int) ($snapshot['category_id'] ?? $workflow->category_id),
                    'type' => $restoredType,
                    'all_customer' => $allCustomer,
                    'rule_chain' => $ruleChain,
                ]);

                if ($allCustomer) {
                    $workflow->customerGroups()->sync([]);
                } else {
                    $workflow->customerGroups()->sync($customerGroupIds);
                }

                $this->backfillWorkflowCustomerGroupTimestamps($workflow->id);
                $scheduleSyncService->sync($workflow);

                $workflow->load('customerGroups:id');
                $request->createVersionSnapshot($workflow, 'restore');

                $restoredVersion = WorkflowVersion::query()
                    ->where('workflow_id', $workflow->id)
                    ->orderByDesc('version_no')
                    ->first();

                $updatedWorkflow = $workflow->fresh(['category', 'creator', 'customerGroups']);
            });
        } catch (InvalidArgumentException $exception) {
            return response_error(msg: $exception->getMessage());
        }

        return response_success([
            'workflow' => $updatedWorkflow,
            'restored_from_version_id' => $version->id,
            'created_version_id' => $restoredVersion?->id,
            'created_version_no' => $restoredVersion?->version_no,
        ], '历史版本还原成功');
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
     * 模板列表
     */
    public function templateList(WorkflowRequest $request): JsonResponse
    {
        $categoryId = $request->input('category_id');

        $templates = WorkflowTemplate::query()
            ->with(['category:id,name'])
            ->when($categoryId, fn (Builder $builder) => $builder->where('category_id', $categoryId))
            ->get();

        return response_success($templates);
    }

    /**
     * 事件配置选项
     */
    public function events(): JsonResponse
    {
        $events = WorkflowEvent::query()
            ->select(['event', 'event_name', 'category_name'])
            ->orderBy('category_name')
            ->orderBy('event')
            ->get();

        $grouped = $events->groupBy('category_name');

        $result = [];
        foreach ($grouped as $categoryName => $categoryEvents) {
            $children = $categoryEvents->map(fn ($item) => [
                'value' => $item->event,
                'label' => $item->event_name,
            ])->values();

            $result[] = [
                'value' => $categoryName,
                'label' => $categoryName,
                'children' => $children,
            ];
        }

        return response_success($result);
    }

    /**
     * @param  array<string, mixed>  $ruleChain
     * @return array<string, mixed>|null
     */
    private function findRuleChainNode(array $ruleChain, string $nodeId): ?array
    {
        $nodes = is_array($ruleChain['nodes'] ?? null) ? $ruleChain['nodes'] : [];
        foreach ($nodes as $node) {
            if (! is_array($node)) {
                continue;
            }

            if ((string) ($node['id'] ?? '') === $nodeId) {
                return $node;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>|null  $node
     * @return array{payload: array<string, mixed>, event: string|null, source: string|null}
     */
    private function resolveStartTriggerPreviewSample(
        ?array $node,
        WorkflowTriggerSampleService $triggerSampleService
    ): array {
        if (! is_array($node)) {
            return [
                'payload' => [],
                'event' => null,
                'source' => null,
            ];
        }

        $parameters = $this->extractNodeParameters($node);
        $events = $this->normalizeTriggerEvents($parameters['triggerEvents'] ?? $parameters['triggerEventsText'] ?? []);
        $event = $events[0] ?? null;
        if ($event === null || $event === '') {
            return [
                'payload' => [],
                'event' => null,
                'source' => null,
            ];
        }

        $sample = $triggerSampleService->fetchTriggerSample($event);
        $samplePayload = is_array($sample['sample_data'] ?? null) ? $sample['sample_data'] : [];

        return [
            'payload' => $samplePayload,
            'event' => (string) ($sample['event'] ?? $event),
            'source' => isset($sample['source']) ? (string) $sample['source'] : null,
        ];
    }

    /**
     * @param  array<string, mixed>  $node
     * @return array<string, mixed>
     */
    private function extractNodeParameters(array $node): array
    {
        foreach (['parameters', 'formData', 'props'] as $field) {
            $value = $node[$field] ?? null;
            if (is_array($value)) {
                return $value;
            }
        }

        return [];
    }

    /**
     * @param  array<int, mixed>|string  $value
     * @return array<int, string>
     */
    private function normalizeTriggerEvents(array|string $value): array
    {
        if (is_array($value)) {
            return array_values(array_filter(array_map(static fn ($item) => trim((string) $item), $value)));
        }

        return array_values(array_filter(array_map('trim', explode(',', (string) $value))));
    }

    private function backfillWorkflowCustomerGroupTimestamps(int $workflowId): void
    {
        $now = now();

        DB::table('workflow_customer_groups')
            ->where('workflow_id', $workflowId)
            ->whereNull('created_at')
            ->update(['created_at' => $now]);

        DB::table('workflow_customer_groups')
            ->where('workflow_id', $workflowId)
            ->whereNull('updated_at')
            ->update(['updated_at' => $now]);
    }

    /**
     * @return array<int, int>
     */
    private function normalizeCustomerGroupIds(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        $ids = array_map(
            static fn ($item) => (int) $item,
            array_filter($value, static fn ($item) => is_numeric($item) && (int) $item > 0)
        );

        return array_values(array_unique($ids));
    }

    /**
     * 批量预览所有节点
     */
    public function batchPreviewNodes(WorkflowRequest $request, WorkflowPreviewService $service): JsonResponse
    {
        $ruleChain = $request->input('rule_chain');
        $nodeIds = $request->input('node_ids');

        try {
            $result = $service->batchPreviewNodes($ruleChain, $nodeIds);
        } catch (Throwable $exception) {
            return response_error($exception->getMessage() ?: '批量预览失败');
        }

        // Persist to database if workflow_id provided
        $workflowId = (int) ($request->input('workflow_id') ?? 0);
        if ($workflowId > 0) {
            $workflow = Workflow::query()->find($workflowId);
            if ($workflow) {
                $storedRuleChain = is_array($workflow->rule_chain) ? $workflow->rule_chain : [];
                $storedMeta = is_array($storedRuleChain['meta'] ?? null) ? $storedRuleChain['meta'] : [];
                $storedMeta['preview_schemas'] = $result['preview_schemas'];
                $storedRuleChain['meta'] = $storedMeta;
                $workflow->rule_chain = $storedRuleChain;
                $workflow->save();
            }
        }

        return response_success($result);
    }

    /**
     * 获取触发器示例数据
     */
    public function triggerSampleData(WorkflowRequest $request, WorkflowTriggerSampleService $service): JsonResponse
    {
        $event = (string) $request->input('event', '');

        try {
            $result = $service->fetchTriggerSample($event);
        } catch (Throwable $exception) {
            return response_error($exception->getMessage() ?: '获取示例数据失败');
        }

        return response_success($result);
    }

    /**
     * 清除预览数据
     */
    public function invalidatePreviewData(WorkflowRequest $request, WorkflowPreviewService $service): JsonResponse
    {
        $workflowId = (int) $request->input('workflow_id', 0);
        $nodeIds = $request->input('node_ids', []);
        $workflow = Workflow::query()->findOrFail($workflowId);

        $ruleChain = is_array($workflow->rule_chain) ? $workflow->rule_chain : [];

        // Find all affected nodes (including downstream)
        $allAffected = [];
        foreach ($nodeIds as $nodeId) {
            $allAffected[] = $nodeId;
            $downstream = $service->findDownstreamNodes($ruleChain, $nodeId);
            $allAffected = array_merge($allAffected, $downstream);
        }
        $allAffected = array_unique($allAffected);

        // Clear preview data
        $meta = is_array($ruleChain['meta'] ?? null) ? $ruleChain['meta'] : [];
        $previewSchemas = is_array($meta['preview_schemas'] ?? null) ? $meta['preview_schemas'] : [];

        foreach ($allAffected as $nodeId) {
            unset($previewSchemas[$nodeId]);
        }

        $meta['preview_schemas'] = $previewSchemas;
        $meta['invalidation_tracking'] = [
            'last_structure_change' => now()->toIso8601String(),
            'affected_node_ids' => $allAffected,
            'change_type' => 'manual_invalidation',
        ];

        $ruleChain['meta'] = $meta;
        $workflow->rule_chain = $ruleChain;
        $workflow->save();

        return response_success([
            'affected_node_ids' => $allAffected,
            'cleared_count' => count($allAffected),
        ]);
    }

    /**
     * 获取业务判断条件字段列表
     */
    public function conditionFields(): JsonResponse
    {
        $fields = WorkflowConditionField::query()->get();

        $grouped = $fields->groupBy('table')->map(function ($items, $table) {
            $tableName = $items->first()->table_name;

            return [
                'value' => $table,
                'label' => $tableName,
                'children' => $items->map(function ($item) {
                    return [
                        'value' => $item->field,
                        'label' => $item->field_name,
                        'field_type' => $item->field_type,
                        'operators' => $item->operators,
                        'component' => $item->component,
                        'component_params' => $item->component_params,
                        'api' => $item->api,
                        'context_binding' => $item->context_binding,
                    ];
                })->values(),
            ];
        })->values();

        return response_success($grouped);
    }
}
