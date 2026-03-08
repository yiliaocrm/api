<?php

namespace App\Http\Requests\Web;

use App\Enums\WorkflowStatus;
use App\Enums\WorkflowType;
use App\Models\Workflow;
use App\Models\WorkflowCategory;
use App\Models\WorkflowVersion;
use App\Services\Workflow\WorkflowPeriodicScheduler;
use BackedEnum;
use Illuminate\Foundation\Http\FormRequest;
use UnitEnum;

class WorkflowRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return match (request()->route()->getActionMethod()) {
            'index' => $this->getIndexRules(),
            'create' => $this->getCreateRules(),
            'update' => $this->getUpdateRules(),
            'remove' => $this->getRemoveRules(),
            'activate' => $this->getActivateRules(),
            'detail', 'deactivate' => $this->getIdRules(),
            'previewNode' => $this->getPreviewNodeRules(),
            'batchPreviewNodes' => $this->getBatchPreviewNodesRules(),
            'triggerSampleData' => $this->getTriggerSampleDataRules(),
            'invalidatePreviewData' => $this->getInvalidatePreviewDataRules(),
            'historyList' => $this->getHistoryListRules(),
            'historyDetail' => $this->getHistoryDetailRules(),
            'historyRestore' => $this->getHistoryRestoreRules(),
            'addCategory' => $this->getAddCategoryRules(),
            'swapCategory' => $this->getSwapCategoryRules(),
            'updateCategory' => $this->getUpdateCategoryRules(),
            'removeCategory' => $this->getRemoveCategoryRules(),
            'templateList' => $this->getTemplateListRules(),
            default => []
        };
    }

    public function messages(): array
    {
        return match (request()->route()->getActionMethod()) {
            'index' => $this->getIndexMessages(),
            'previewNode' => $this->getPreviewNodeMessages(),
            'batchPreviewNodes' => $this->getBatchPreviewNodesMessages(),
            'triggerSampleData' => $this->getTriggerSampleDataMessages(),
            'invalidatePreviewData' => $this->getInvalidatePreviewDataMessages(),
            'addCategory' => $this->getAddCategoryMessages(),
            'swapCategory' => $this->getSwapCategoryMessages(),
            'updateCategory' => $this->getUpdateCategoryMessages(),
            'removeCategory' => $this->getRemoveCategoryMessages(),
            'templateList' => $this->getTemplateListMessages(),
            default => []
        };
    }

    /**
     * 获取 create/update 可持久化字段
     */
    public function fillData(): array
    {
        return match (request()->route()->getActionMethod()) {
            'create' => $this->getCreateData(),
            'update' => $this->getUpdateData(),
            default => [],
        };
    }

    /**
     * 获取客户分组 ID 列表
     */
    public function customerGroupIds(): ?array
    {
        $validated = $this->validated();

        return match (request()->route()->getActionMethod()) {
            'create' => $validated['customer_group_ids'] ?? [],
            'update' => array_key_exists('customer_group_ids', $validated)
                ? $validated['customer_group_ids']
                : null,
            default => null,
        };
    }

    /**
     * 获取快照来源
     */
    public function saveSource(): string
    {
        $source = (string) $this->input('save_source', 'save');

        return in_array($source, ['save', 'publish', 'restore'], true) ? $source : 'save';
    }

    private function getAddCategoryRules(): array
    {
        return [
            'name' => 'required|string|max:255',
        ];
    }

    private function getAddCategoryMessages(): array
    {
        return [
            'name.required' => '分类名称不能为空',
            'name.string' => '分类名称必须是字符串',
            'name.max' => '分类名称不能超过255个字符',
        ];
    }

    private function getUpdateCategoryRules(): array
    {
        return [
            'id' => 'required|integer|exists:workflow_categories,id',
            'name' => 'required|string|max:255',
        ];
    }

    private function getUpdateCategoryMessages(): array
    {
        return [
            'id.required' => '分类ID不能为空',
            'id.integer' => '分类ID必须是整数',
            'id.exists' => '分类ID不存在',
            'name.required' => '分类名称不能为空',
            'name.string' => '分类名称必须是字符串',
            'name.max' => '分类名称不能超过255个字符',
        ];
    }

    private function getRemoveCategoryRules(): array
    {
        return [
            'id' => [
                'required',
                'integer',
                'exists:workflow_categories,id',
                // 判断是否有工作流使用该分类
                function ($attribute, $value, $fail) {
                    if (Workflow::query()->where('category_id', $value)->exists()) {
                        $fail('该分类下有工作流，不能删除');

                        return;
                    }
                },
            ],
        ];
    }

    private function getRemoveCategoryMessages(): array
    {
        return [
            'id.required' => '分类ID不能为空',
            'id.integer' => '分类ID必须是整数',
            'id.exists' => '分类ID不存在',
            'id.custom' => '该分类下有工作流，不能删除',
        ];
    }

    private function getSwapCategoryRules(): array
    {
        return [
            'id1' => 'required|integer|exists:workflow_categories,id',
            'id2' => 'required|integer|exists:workflow_categories,id',
        ];
    }

    private function getSwapCategoryMessages(): array
    {
        return [
            'id1.required' => '第一个分类ID不能为空',
            'id1.integer' => '第一个分类ID必须是整数',
            'id1.exists' => '第一个分类ID不存在',
            'id2.required' => '第二个分类ID不能为空',
            'id2.integer' => '第二个分类ID必须是整数',
            'id2.exists' => '第二个分类ID不存在',
        ];
    }

    private function getIndexRules(): array
    {
        return [
            'name' => 'nullable|string|max:255',
            'category_id' => [
                'nullable',
                'integer',
                function ($attribute, $value, $fail) {
                    if ($value && ! WorkflowCategory::query()->where('id', $value)->exists()) {
                        $fail('分类ID不存在');
                    }
                },
            ],
        ];
    }

    private function getIndexMessages(): array
    {
        return [
            'name.string' => '工作流名称必须是字符串',
            'name.max' => '工作流名称不能超过255个字符',
            'category_id.integer' => '分类ID必须是整数',
            'category_id.exists' => '分类ID不存在',
        ];
    }

    private function getTemplateListRules(): array
    {
        return [
            'category_id' => 'nullable|integer|exists:workflow_template_categories,id',
        ];
    }

    private function getTemplateListMessages(): array
    {
        return [
            'category_id.integer' => '模板分类ID必须是整数',
            'category_id.exists' => '模板分类ID不存在',
        ];
    }

    private function getCreateRules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category_id' => 'required|exists:workflow_categories,id',
            'type' => 'required|in:trigger,periodic',
            'all_customer' => 'boolean',
            'customer_group_ids' => 'array',
            'customer_group_ids.*' => 'exists:customer_groups,id',
            'rule_chain' => 'required|array',
            'save_source' => 'nullable|in:save,publish,restore',
        ];
    }

    private function getUpdateRules(): array
    {
        return [
            'id' => 'required|exists:workflows,id',
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'category_id' => 'sometimes|required|exists:workflow_categories,id',
            'type' => 'sometimes|required|in:trigger,periodic',
            'all_customer' => 'boolean',
            'customer_group_ids' => 'array',
            'customer_group_ids.*' => 'exists:customer_groups,id',
            'rule_chain' => 'nullable|array',
            'save_source' => 'nullable|in:save,publish,restore',
        ];
    }

    private function getIdRules(): array
    {
        return [
            'id' => 'required|exists:workflows,id',
        ];
    }

    private function getRemoveRules(): array
    {
        return [
            'id' => [
                'required',
                'exists:workflows,id',
                function ($attribute, $value, $fail) {
                    $workflow = Workflow::query()->find($value);
                    if (! $workflow) {
                        return;
                    }

                    if ($workflow->status === WorkflowStatus::ACTIVE) {
                        $fail('已发布状态的工作流不可删除，请先取消发布');
                    }
                },
            ],
        ];
    }

    private function getActivateRules(): array
    {
        return [
            'id' => [
                'required',
                'exists:workflows,id',
                function ($attribute, $value, $fail) {
                    $workflow = Workflow::query()->find($value);
                    if (! $workflow) {
                        return;
                    }

                    if (empty($workflow->rule_chain)) {
                        $fail('工作流没有配置规则链，无法发布');

                        return;
                    }

                    $validation = $this->validateRuleChainForRuntime(
                        is_array($workflow->rule_chain) ? $workflow->rule_chain : null
                    );
                    if (! $validation['valid']) {
                        $fail($validation['message']);

                        return;
                    }

                    $startNodeType = $this->detectStartNodeType($workflow->rule_chain);
                    $expectedStartType = $workflow->type === WorkflowType::PERIODIC
                        ? 'start_periodic'
                        : 'start_trigger';

                    if ($startNodeType !== $expectedStartType) {
                        $fail(sprintf(
                            '工作流类型为 [%s]，但开始节点为 [%s]，类型不匹配',
                            $workflow->type instanceof \BackedEnum ? $workflow->type->value : $workflow->type,
                            $startNodeType ?? 'unknown',
                        ));

                        return;
                    }

                    if ($workflow->type === WorkflowType::PERIODIC) {
                        $scheduler = app(WorkflowPeriodicScheduler::class);
                        $periodicConfig = $scheduler->extractPeriodicConfig(
                            is_array($workflow->rule_chain) ? $workflow->rule_chain : []
                        );

                        if ($periodicConfig === null) {
                            $fail('周期型工作流缺少开始节点配置');
                        }
                    }
                },
            ],
        ];
    }

    private function getPreviewNodeRules(): array
    {
        return [
            'workflow_id' => 'nullable|integer|exists:workflows,id',
            'rule_chain' => 'required|array',
            'node_id' => 'required|string',
        ];
    }

    private function getPreviewNodeMessages(): array
    {
        return [
            'rule_chain.required' => '预览失败：规则链格式无效',
            'rule_chain.array' => '预览失败：规则链格式无效',
        ];
    }

    private function getBatchPreviewNodesRules(): array
    {
        return [
            'rule_chain' => 'required|array',
            'node_ids' => 'nullable|array',
        ];
    }

    private function getBatchPreviewNodesMessages(): array
    {
        return [
            'rule_chain.required' => '批量预览失败：规则链格式无效',
            'rule_chain.array' => '批量预览失败：规则链格式无效',
            'node_ids.array' => '批量预览失败：node_ids 必须是数组',
        ];
    }

    private function getTriggerSampleDataRules(): array
    {
        return [
            'event' => 'required|string',
        ];
    }

    private function getTriggerSampleDataMessages(): array
    {
        return [
            'event.required' => '请提供事件类型',
        ];
    }

    private function getInvalidatePreviewDataRules(): array
    {
        return [
            'workflow_id' => 'required|integer|min:1|exists:workflows,id',
            'node_ids' => 'required|array|min:1',
        ];
    }

    private function getInvalidatePreviewDataMessages(): array
    {
        return [
            'workflow_id.required' => '请提供工作流ID',
            'workflow_id.integer' => '请提供工作流ID',
            'workflow_id.min' => '请提供工作流ID',
            'workflow_id.exists' => '工作流不存在',
            'node_ids.required' => '请提供要清除的节点ID列表',
            'node_ids.array' => '请提供要清除的节点ID列表',
            'node_ids.min' => '请提供要清除的节点ID列表',
        ];
    }

    private function getHistoryListRules(): array
    {
        return [
            'workflow_id' => 'required|integer|exists:workflows,id',
            'rows' => 'nullable|integer|min:1|max:200',
            'page' => 'nullable|integer|min:1',
        ];
    }

    private function getHistoryDetailRules(): array
    {
        return [
            'id' => 'required|integer|exists:workflow_versions,id',
        ];
    }

    private function getHistoryRestoreRules(): array
    {
        return [
            'id' => 'required|integer|exists:workflow_versions,id',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function getCreateData(): array
    {
        $validated = $this->validated();
        $data = [];

        foreach (['name', 'description', 'category_id', 'type', 'all_customer', 'rule_chain'] as $field) {
            if (array_key_exists($field, $validated)) {
                $data[$field] = $validated[$field];
            }
        }

        $data['create_user_id'] = user()->id;
        $data['status'] = 'paused';

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    private function getUpdateData(): array
    {
        $validated = $this->validated();
        $data = [];

        // cron 字段由后端根据 rule_chain 自动生成，不从前端写入
        foreach (['name', 'description', 'category_id', 'type', 'all_customer', 'rule_chain'] as $field) {
            if (array_key_exists($field, $validated)) {
                $data[$field] = $validated[$field];
            }
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>|null  $ruleChain
     * @return array{valid: bool, message: string}
     */
    public function validateRuleChainForRuntime(?array $ruleChain): array
    {
        if (! is_array($ruleChain)) {
            return ['valid' => false, 'message' => '工作流规则链格式不正确，请检查后重试'];
        }

        $nodes = $ruleChain['nodes'] ?? [];
        $connections = $ruleChain['connections'] ?? [];
        if (! is_array($nodes) || empty($nodes)) {
            return ['valid' => false, 'message' => '工作流规则链节点不能为空'];
        }
        if (! is_array($connections)) {
            return ['valid' => false, 'message' => '工作流连线配置无效'];
        }

        $supportedNodes = ['start_trigger', 'start_periodic', 'wait', 'condition_business', 'create_followup', 'log', 'end'];
        $startCount = 0;
        $hasEnd = false;
        $conditionBusinessNodes = []; // nodeId => groups count
        $nodeIdSet = [];
        $nodeTypeMap = [];
        $startNodeType = null;

        foreach ($nodes as $node) {
            $nodeId = (string) ($node['id'] ?? '');
            $type = strtolower((string) ($node['type'] ?? ''));
            if ($nodeId !== '') {
                $nodeIdSet[$nodeId] = true;
                $nodeTypeMap[$nodeId] = $type;
            }

            if (in_array($type, ['start_trigger', 'start_periodic'], true)) {
                $startCount++;
                $startNodeType = $type;
            }

            if ($type === 'end') {
                $hasEnd = true;
            }

            if ($type === 'condition_business') {
                $parameters = $this->extractNodeParameters($node);
                $groups = $parameters['groups'] ?? null;
                if (! is_array($groups) || empty($groups)) {
                    return ['valid' => false, 'message' => "condition_business 节点 [{$nodeId}] 至少需要一个条件组"];
                }

                foreach ($groups as $groupIndex => $group) {
                    $groupRules = $group['rules'] ?? null;
                    if (! is_array($groupRules) || empty($groupRules)) {
                        return ['valid' => false, 'message' => "condition_business 节点 [{$nodeId}] 条件组 ".($groupIndex + 1).' 至少需要一条规则'];
                    }
                    foreach ($groupRules as $ruleIndex => $rule) {
                        if (empty($rule['table'] ?? '') || empty($rule['field'] ?? '')) {
                            return ['valid' => false, 'message' => "condition_business 节点 [{$nodeId}] 条件组 ".($groupIndex + 1).' 规则 '.($ruleIndex + 1).' 缺少表名或字段'];
                        }
                    }
                }

                $conditionBusinessNodes[$nodeId] = count($groups);
            }

            if ($type === 'create_followup') {
                $parameters = $this->extractNodeParameters($node);
                if (trim((string) ($parameters['title'] ?? '')) === '') {
                    return ['valid' => false, 'message' => "create_followup 节点 [{$nodeId}] 缺少回访标题"];
                }

                if (empty($parameters['type'])) {
                    return ['valid' => false, 'message' => "create_followup 节点 [{$nodeId}] 缺少回访类型"];
                }

                $userMode = $parameters['followup_user_mode'] ?? 'specified';
                if ($userMode === 'specified' && empty($parameters['followup_user'])) {
                    return ['valid' => false, 'message' => "create_followup 节点 [{$nodeId}] 缺少提醒人员"];
                }
                if ($userMode === 'relation' && empty($parameters['followup_user_relation'])) {
                    return ['valid' => false, 'message' => "create_followup 节点 [{$nodeId}] 缺少归属关系配置"];
                }

                $dateMode = strtolower(trim((string) ($parameters['date_mode'] ?? 'relative')));
                if ($dateMode === 'relative') {
                    $dateOffset = (int) ($parameters['date_offset'] ?? 0);
                    if ($dateOffset < 0) {
                        return ['valid' => false, 'message' => "create_followup 节点 [{$nodeId}] 的相对时间偏移必须大于等于 0"];
                    }

                    $dateUnit = strtolower(trim((string) ($parameters['date_unit'] ?? '')));
                    if (! in_array($dateUnit, ['hours', 'days', 'weeks'], true)) {
                        return ['valid' => false, 'message' => "create_followup 节点 [{$nodeId}] 的时间单位无效"];
                    }
                } elseif ($dateMode === 'absolute') {
                    if (trim((string) ($parameters['absolute_date'] ?? '')) === '') {
                        return ['valid' => false, 'message' => "create_followup 节点 [{$nodeId}] 缺少绝对时间"];
                    }
                } else {
                    return ['valid' => false, 'message' => "create_followup 节点 [{$nodeId}] 的日期模式无效"];
                }
            }

            if (! in_array($type, $supportedNodes, true)) {
                return ['valid' => false, 'message' => "检测到不支持的节点类型 [{$type}]，请移除后再激活"];
            }
        }

        if ($startCount !== 1) {
            return ['valid' => false, 'message' => '规则链必须且只能包含一个开始节点'];
        }

        if (! $hasEnd) {
            return ['valid' => false, 'message' => '规则链缺少结束节点 end'];
        }

        // 运行时兜底：禁止等待节点直接连接等待节点
        foreach ($connections as $connection) {
            if (! is_array($connection)) {
                continue;
            }

            $source = (string) ($connection['source'] ?? '');
            $target = (string) ($connection['target'] ?? '');
            if ($source === '' || $target === '') {
                continue;
            }

            $sourceType = strtolower((string) ($nodeTypeMap[$source] ?? ''));
            $targetType = strtolower((string) ($nodeTypeMap[$target] ?? ''));
            if ($sourceType === 'wait' && $targetType === 'wait') {
                return ['valid' => false, 'message' => '等待节点后不能直接连接等待节点'];
            }
        }

        foreach ($conditionBusinessNodes as $cbNodeId => $groupCount) {
            $branchConnections = array_values(array_filter($connections, fn ($connection) => is_array($connection)
                && (string) ($connection['source'] ?? '') === $cbNodeId));

            // 需要 groupCount 个条件端口 + 1 个 default 端口
            $expectedPortCount = $groupCount + 1;
            if (count($branchConnections) < $expectedPortCount) {
                return ['valid' => false, 'message' => "condition_business 节点 [{$cbNodeId}] 分支连接数量不足，需要 {$expectedPortCount} 个"];
            }

            $portSet = [];
            foreach ($branchConnections as $connection) {
                if (($connection['type'] ?? 'main') !== 'branch') {
                    return ['valid' => false, 'message' => "condition_business 节点 [{$cbNodeId}] 的出口连线必须为 branch 类型"];
                }

                $sourcePort = strtolower(trim((string) ($connection['sourcePort'] ?? '')));
                if (isset($portSet[$sourcePort])) {
                    return ['valid' => false, 'message' => "condition_business 节点 [{$cbNodeId}] 端口 [{$sourcePort}] 存在重复连线"];
                }

                $target = (string) ($connection['target'] ?? '');
                if ($target === '' || ! isset($nodeIdSet[$target])) {
                    return ['valid' => false, 'message' => "condition_business 节点 [{$cbNodeId}] 存在无效目标节点"];
                }

                $portSet[$sourcePort] = true;
            }

            // 检查 default 端口
            if (! isset($portSet['default'])) {
                return ['valid' => false, 'message' => "condition_business 节点 [{$cbNodeId}] 缺少默认分支出口"];
            }

            // 检查每个条件端口
            for ($i = 1; $i <= $groupCount; $i++) {
                if (! isset($portSet["cond_{$i}"])) {
                    return ['valid' => false, 'message' => "condition_business 节点 [{$cbNodeId}] 缺少条件分支 cond_{$i} 出口"];
                }
            }
        }

        return ['valid' => true, 'message' => 'ok'];
    }

    /**
     * 创建工作流历史快照
     */
    public function createVersionSnapshot(Workflow $workflow, string $source = 'save'): void
    {
        $normalizedSource = in_array($source, ['save', 'publish', 'restore'], true) ? $source : 'save';
        $nextVersionNo = (int) WorkflowVersion::query()
            ->where('workflow_id', $workflow->id)
            ->max('version_no') + 1;

        $customerGroupIds = $workflow->relationLoaded('customerGroups')
            ? $workflow->customerGroups->pluck('id')->values()->all()
            : $workflow->customerGroups()->pluck('customer_groups.id')->values()->all();

        WorkflowVersion::query()->create([
            'workflow_id' => $workflow->id,
            'version_no' => $nextVersionNo,
            'source' => $normalizedSource,
            'create_user_id' => user()?->id,
            'snapshot' => [
                'workflow_id' => $workflow->id,
                'name' => (string) $workflow->name,
                'description' => $workflow->description,
                'category_id' => $workflow->category_id,
                'type' => $this->normalizeSnapshotScalar($workflow->type),
                'all_customer' => (bool) $workflow->all_customer,
                'customer_group_ids' => $customerGroupIds,
                'cron' => $workflow->cron,
                'rule_chain' => $workflow->rule_chain,
                'status' => $this->normalizeSnapshotScalar($workflow->status),
                'saved_at' => now()->toDateTimeString(),
            ],
        ]);
    }

    private function normalizeSnapshotScalar(mixed $value): mixed
    {
        if ($value instanceof BackedEnum) {
            return $value->value;
        }

        if ($value instanceof UnitEnum) {
            return $value->name;
        }

        return $value;
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
     * 从 rule_chain 中检测开始节点类型
     */
    private function detectStartNodeType(mixed $ruleChain): ?string
    {
        $nodes = is_array($ruleChain['nodes'] ?? null) ? $ruleChain['nodes'] : [];

        foreach ($nodes as $node) {
            $type = strtolower((string) ($node['type'] ?? ''));
            if (in_array($type, ['start_trigger', 'start_periodic'], true)) {
                return $type;
            }
        }

        return null;
    }
}
