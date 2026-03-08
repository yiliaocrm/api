<?php

namespace App\Listeners\Workflow;

use App\Events\Web\WorkflowTriggerEvent;
use App\Jobs\Workflow\RunWorkflowExecutionJob;
use App\Models\Workflow;
use App\Models\WorkflowExecution;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DispatchWorkflowExecutionFromTrigger
{
    /**
     * 处理工作流触发事件并分发可执行的流程实例。
     *
     * 执行顺序：
     * 1. 仅筛选激活中的触发型工作流；
     * 2. 校验触发事件是否命中 start_trigger 配置；
     * 3. 校验触发顾客是否命中目标人群；
     * 4. 创建执行记录并派发运行 Job。
     */
    public function handle(WorkflowTriggerEvent $event): void
    {
        $workflows = Workflow::query()
            ->where('status', 'active')
            ->where('type', 'trigger')
            ->with('customerGroups:id')
            ->get(['id', 'rule_chain', 'all_customer']);

        foreach ($workflows as $workflow) {
            if (! $this->isWorkflowMatched($workflow->rule_chain, $event->eventName)) {
                continue;
            }
            if (! $this->isTargetCustomerMatched($workflow, $event)) {
                continue;
            }

            // 获取最新版本
            $latestVersion = $workflow->versions()->latest('version_no')->first();

            $execution = WorkflowExecution::query()->create([
                'workflow_id' => $workflow->id,
                'workflow_version_id' => $latestVersion?->id,
                'status' => 'running',
                'started_at' => null,
                'input_data' => $event->payload,
                'trigger_type' => 'event',
                'trigger_event' => $event->eventName,
                'trigger_model_type' => $event->modelType,
                'trigger_model_id' => (string) $event->modelId,
                'context_data' => [
                    'trigger' => [
                        'event' => $event->eventName,
                        'model_type' => $event->modelType,
                        'model_id' => (string) $event->modelId,
                        'tenant_id' => tenant('id'),
                        'triggered_at' => Carbon::now()->toIso8601String(),
                    ],
                    'payload' => $event->payload,
                    'runtime' => [
                        'steps' => [],
                    ],
                ],
            ]);

            dispatch(new RunWorkflowExecutionJob($execution->id));

            // 触发型旅程执行时更新最后运行时间
            $workflow->update(['last_run_at' => now()]);
        }
    }

    /**
     * 判断当前工作流是否匹配本次触发事件。
     *
     * @param  array<string, mixed>|null  $ruleChain
     */
    private function isWorkflowMatched(?array $ruleChain, string $eventName): bool
    {
        if (! is_array($ruleChain)) {
            return false;
        }

        $nodes = $ruleChain['nodes'] ?? [];
        if (! is_array($nodes)) {
            return false;
        }

        foreach ($nodes as $node) {
            if (($node['type'] ?? null) !== 'start_trigger') {
                continue;
            }

            $parameters = $this->extractParameters($node);
            $events = $this->normalizeEvents($parameters['triggerEvents'] ?? $parameters['triggerEventsText'] ?? []);

            return in_array($eventName, $events, true);
        }

        return false;
    }

    /**
     * 从节点配置中提取参数（按 parameters/formData/props 兜底）。
     *
     * @param  array<string, mixed>  $node
     * @return array<string, mixed>
     */
    private function extractParameters(array $node): array
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
     * 统一标准化触发事件配置，兼容数组和逗号分隔字符串。
     *
     * @param  array<int, mixed>|string  $value
     * @return array<int, string>
     */
    private function normalizeEvents(array|string $value): array
    {
        if (is_array($value)) {
            return array_values(array_filter(array_map('strval', $value)));
        }

        return array_values(array_filter(array_map('trim', explode(',', (string) $value))));
    }

    /**
     * 判断触发顾客是否命中该工作流目标人群。
     *
     * 规则：
     * - all_customer=true：直接命中；
     * - all_customer=false：必须存在顾客ID、必须绑定分群、且顾客在分群明细中。
     */
    private function isTargetCustomerMatched(Workflow $workflow, WorkflowTriggerEvent $event): bool
    {
        if ((bool) $workflow->all_customer) {
            return true;
        }

        $customerId = $this->extractTriggeredCustomerId($event);
        if ($customerId === null) {
            return false;
        }

        $groupIds = $workflow->customerGroups
            ->pluck('id')
            ->map(static fn ($id) => (int) $id)
            ->filter(static fn (int $id) => $id > 0)
            ->values()
            ->all();

        if (empty($groupIds)) {
            return false;
        }

        return DB::table('customer_group_details')
            ->where('customer_id', $customerId)
            ->whereIn('customer_group_id', $groupIds)
            ->exists();
    }

    /**
     * 提取本次触发对应的顾客ID。
     *
     * 优先级：
     * 1. modelType=customer 时使用 modelId；
     * 2. 兜底读取 payload.customer_id。
     */
    private function extractTriggeredCustomerId(WorkflowTriggerEvent $event): ?string
    {
        if (strtolower($event->modelType) === 'customer' && (string) $event->modelId !== '') {
            return (string) $event->modelId;
        }

        $payloadCustomerId = data_get($event->payload, 'customer_id');
        if ($payloadCustomerId === null || $payloadCustomerId === '') {
            return null;
        }

        return (string) $payloadCustomerId;
    }
}
