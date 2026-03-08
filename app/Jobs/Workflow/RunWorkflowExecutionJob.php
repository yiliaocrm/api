<?php

namespace App\Jobs\Workflow;

use App\Helpers\ParseWorkflowConditionField;
use App\Models\Customer;
use App\Models\WorkflowExecution;
use App\Models\WorkflowExecutionStep;
use App\Services\Workflow\ContextResolver;
use App\Services\Workflow\Executors\CreateFollowupExecutor;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use RuntimeException;
use Throwable;

class RunWorkflowExecutionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private const int MAX_STEPS = 200;

    private ContextResolver $contextResolver;

    private array $nameMap = [];

    public function __construct(public int $executionId) {}

    public function handle(): void
    {
        $execution = WorkflowExecution::query()
            ->with('workflow:id,status,rule_chain')
            ->find($this->executionId);

        if (! $execution || ! $execution->workflow) {
            return;
        }

        if (! in_array($execution->status->value, ['running', 'waiting'], true)) {
            return;
        }

        if ($execution->workflow->status !== \App\Enums\WorkflowStatus::ACTIVE) {
            return;
        }

        $ruleChain = $execution->workflow->rule_chain;
        if (! is_array($ruleChain)) {
            $this->failExecution($execution, '规则链数据无效');

            return;
        }

        // 初始化上下文解析器和名称映射
        $this->contextResolver = new ContextResolver;
        $this->nameMap = $this->contextResolver->buildNameMapFromWorkflow($ruleChain);

        $nodes = is_array($ruleChain['nodes'] ?? null) ? $ruleChain['nodes'] : [];
        $connections = is_array($ruleChain['connections'] ?? null) ? $ruleChain['connections'] : [];
        $flow = is_array($ruleChain['layout']['flow'] ?? null) ? $ruleChain['layout']['flow'] : [];

        if (empty($nodes)) {
            $this->failExecution($execution, '规则链缺少节点');

            return;
        }

        $nodeMap = [];
        foreach ($nodes as $node) {
            $nodeId = (string) ($node['id'] ?? '');
            if ($nodeId !== '') {
                $nodeMap[$nodeId] = $node;
            }
        }

        $orderMap = [];
        foreach ($flow as $item) {
            $nodeId = (string) ($item['nodeId'] ?? '');
            if ($nodeId === '') {
                continue;
            }
            $orderMap[$nodeId] = (int) ($item['order'] ?? 0);
        }

        $context = is_array($execution->context_data) ? $execution->context_data : [];
        $context['runtime'] = is_array($context['runtime'] ?? null) ? $context['runtime'] : [];
        $context['runtime']['steps'] = is_array($context['runtime']['steps'] ?? null) ? $context['runtime']['steps'] : [];
        $context['runtime']['node_outputs'] = is_array($context['runtime']['node_outputs'] ?? null)
            ? $context['runtime']['node_outputs']
            : [];
        $wasWaiting = $execution->status->value === 'waiting';

        if (! $execution->started_at) {
            $execution->started_at = now();
        }
        $execution->status = 'running';
        $execution->waiting_until = null;
        $execution->save();

        $currentNodeId = $wasWaiting
            ? ($execution->next_node_id ?: $execution->current_node_id)
            : ($execution->current_node_id ?: $this->resolveStartNodeId($nodes));
        if (! $currentNodeId) {
            $this->failExecution($execution, '未找到触发开始节点');

            return;
        }

        $stepCount = 0;
        while ($currentNodeId && $stepCount < self::MAX_STEPS) {
            $stepCount++;

            $node = $nodeMap[$currentNodeId] ?? null;
            if (! is_array($node)) {
                $this->failExecution($execution, "节点不存在: {$currentNodeId}");

                return;
            }

            $nodeType = strtolower((string) ($node['type'] ?? ''));
            $nodeName = (string) ($node['name'] ?? $node['nodeName'] ?? $nodeType);
            $parameters = $this->extractParameters($node);
            $stepStartAt = now();

            // 获取上一个节点的输出数据
            $prevNodeOutput = $this->resolvePrevNodeOutput(
                $currentNodeId,
                $connections,
                $execution,
                $context,
                $wasWaiting,
                $nodeMap
            );
            // 重置标记，后续节点应通过 connections 查找前置节点
            $wasWaiting = false;

            $step = WorkflowExecutionStep::query()->create([
                'workflow_execution_id' => $execution->id,
                'workflow_version_id' => $execution->workflow_version_id,
                'node_id' => $currentNodeId,
                'node_type' => $nodeType,
                'node_name' => $nodeName,
                'status' => 'running',
                'attempt' => $this->nextAttempt($execution->id, $currentNodeId),
                'input_data' => [
                    'parameters' => $parameters,
                    'from_node_id' => $prevNodeOutput['node_id'],
                    'from_node_name' => $prevNodeOutput['node_name'],
                ],
                'started_at' => $stepStartAt,
            ]);

            try {
                if ($nodeType === 'start_trigger') {
                    // 构建完整的输出，包含触发数据，以便下游节点可以通过节点引用访问
                    $output = [
                        'started' => true,
                        'trigger' => $context['trigger'] ?? null,
                        'payload' => $context['payload'] ?? null,
                    ];
                    $nextNodeId = $this->resolveNextNodeId($currentNodeId, $connections, $orderMap);
                    $this->finishStep($step, 'success', $output, null, $stepStartAt);
                    $this->appendRuntimeStep($context, $currentNodeId, $nodeType, 'success', $output);
                    $this->advanceExecution($execution, $context, $nextNodeId);
                    $currentNodeId = $nextNodeId;

                    continue;
                }

                if ($nodeType === 'start_periodic') {
                    // 周期型开始节点：从 context 中读取 customer_id 并加载客户数据
                    $customerId = $context['trigger']['customer_id'] ?? null;
                    $customerPayload = [];

                    if ($customerId) {
                        $customer = Customer::query()->find($customerId);
                        if ($customer) {
                            $customerPayload = $customer->toArray();
                        }
                    }

                    // 将客户数据写入 context.payload，与触发型的 payload 结构对齐
                    $context['payload'] = $customerPayload;

                    $output = [
                        'started' => true,
                        'trigger' => $context['trigger'] ?? null,
                        'payload' => $customerPayload,
                        'periodic_config' => $parameters,
                    ];
                    $nextNodeId = $this->resolveNextNodeId($currentNodeId, $connections, $orderMap);
                    $this->finishStep($step, 'success', $output, null, $stepStartAt);
                    $this->appendRuntimeStep($context, $currentNodeId, $nodeType, 'success', $output);
                    $this->advanceExecution($execution, $context, $nextNodeId);
                    $currentNodeId = $nextNodeId;

                    continue;
                }

                if ($nodeType === 'wait') {
                    $nextNodeId = $this->resolveNextNodeId($currentNodeId, $connections, $orderMap);
                    $waitingUntil = $this->calculateWaitUntil($parameters);
                    $output = ['waiting_until' => $waitingUntil->toIso8601String()];

                    $this->finishStep($step, 'success', $output, null, $stepStartAt);
                    $this->appendRuntimeStep($context, $currentNodeId, $nodeType, 'success', $output);

                    $execution->status = 'waiting';
                    $execution->current_node_id = $currentNodeId;
                    $execution->next_node_id = $nextNodeId;
                    $execution->waiting_until = $waitingUntil;
                    $execution->context_data = $context;
                    $execution->lock_version = ((int) $execution->lock_version) + 1;
                    $execution->save();

                    return;
                }

                if ($nodeType === 'condition_business') {
                    $result = $this->executeConditionBusinessNode($currentNodeId, $parameters, $context);
                    $branchPort = $result['matched_port'];
                    $nextNodeId = $this->resolveBranchNextNodeId(
                        $currentNodeId,
                        $branchPort,
                        $connections,
                        $orderMap
                    );

                    if (! $nextNodeId) {
                        throw new RuntimeException("condition_business 节点 [{$currentNodeId}] 缺少 {$branchPort} 分支连接");
                    }

                    $context['runtime']['condition_business'] = is_array($context['runtime']['condition_business'] ?? null)
                        ? $context['runtime']['condition_business']
                        : [];
                    $context['runtime']['condition_business'][$currentNodeId] = [
                        'matched' => $result['matched'],
                        'matched_branch' => $branchPort,
                        'matched_group_index' => $result['matched_group_index'],
                        'evaluated_at' => now()->toIso8601String(),
                    ];

                    $output = [
                        'matched' => $result['matched'],
                        'matched_branch' => $branchPort,
                        'matched_group_index' => $result['matched_group_index'],
                    ];

                    $this->finishStep($step, 'success', $output, null, $stepStartAt);
                    $this->appendRuntimeStep($context, $currentNodeId, $nodeType, 'success', $output);
                    $this->advanceExecution($execution, $context, $nextNodeId);
                    $currentNodeId = $nextNodeId;

                    continue;
                }

                if ($nodeType === 'create_followup') {
                    /** @var CreateFollowupExecutor $executor */
                    $executor = app(CreateFollowupExecutor::class);
                    $output = $executor->execute($execution, [
                        'type' => $nodeType,
                        'configuration' => $parameters,
                    ]);

                    if (! (bool) ($output['created'] ?? false)) {
                        $errorMessage = trim((string) ($output['error'] ?? '创建回访任务失败'));
                        throw new RuntimeException($errorMessage !== '' ? $errorMessage : '创建回访任务失败');
                    }

                    $nextNodeId = $this->resolveNextNodeId($currentNodeId, $connections, $orderMap);

                    $this->finishStep($step, 'success', $output, null, $stepStartAt);
                    $this->appendRuntimeStep($context, $currentNodeId, $nodeType, 'success', $output);
                    $this->advanceExecution($execution, $context, $nextNodeId);
                    $currentNodeId = $nextNodeId;

                    continue;
                }

                if ($nodeType === 'log') {
                    $output = $this->executeLogNode($execution, $currentNodeId, $parameters, $context);
                    $nextNodeId = $this->resolveNextNodeId($currentNodeId, $connections, $orderMap);

                    $this->finishStep($step, 'success', $output, null, $stepStartAt);
                    $this->appendRuntimeStep($context, $currentNodeId, $nodeType, 'success', $output);
                    $this->advanceExecution($execution, $context, $nextNodeId);
                    $currentNodeId = $nextNodeId;

                    continue;
                }

                if ($nodeType === 'end') {
                    $output = ['ended' => true];
                    $this->finishStep($step, 'success', $output, null, $stepStartAt);
                    $this->appendRuntimeStep($context, $currentNodeId, $nodeType, 'success', $output);
                    $this->completeExecution($execution, $context);

                    return;
                }

                throw new RuntimeException("节点类型 [{$nodeType}] 当前版本暂不支持执行");
            } catch (Throwable $exception) {
                $this->finishStep($step, 'error', null, $exception->getMessage(), $stepStartAt);
                $this->failExecution($execution, $exception->getMessage(), $context);

                return;
            }
        }

        if ($stepCount >= self::MAX_STEPS) {
            $this->failExecution($execution, '执行步数超过安全上限');
        } elseif (! $currentNodeId) {
            $this->completeExecution($execution, $context);
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $nodes
     */
    private function resolveStartNodeId(array $nodes): ?string
    {
        foreach ($nodes as $node) {
            $type = $node['type'] ?? null;
            if (in_array($type, ['start_trigger', 'start_periodic'], true)) {
                return (string) ($node['id'] ?? '');
            }
        }

        return null;
    }

    /**
     * 获取上一个节点的输出数据
     *
     * @param  array<int, array<string, mixed>>  $connections
     * @return array{node_id: ?string, node_name: ?string, output: ?array}
     */
    private function resolvePrevNodeOutput(
        string $currentNodeId,
        array $connections,
        WorkflowExecution $execution,
        array $context,
        bool $wasWaiting,
        array $nodeMap
    ): array {
        // 场景 B：等待节点恢复（waiting -> running）
        // 当前节点来自 execution->next_node_id，上一节点是 execution->current_node_id（wait 节点）
        if ($wasWaiting) {
            $prevNodeId = $execution->current_node_id;
            if ($prevNodeId && isset($nodeMap[$prevNodeId])) {
                $prevNode = $nodeMap[$prevNodeId];
                $prevNodeName = (string) ($prevNode['name'] ?? $prevNode['nodeName'] ?? $prevNode['type'] ?? '');
                $prevNodeOutput = $context['runtime']['node_outputs'][$prevNodeId] ?? null;

                return [
                    'node_id' => $prevNodeId,
                    'node_name' => $prevNodeName,
                    'output' => $prevNodeOutput,
                ];
            }

            return ['node_id' => null, 'node_name' => null, 'output' => null];
        }

        // 场景 A/D：正常流程执行或开始节点
        // 从 connections 中找到指向当前节点的源节点
        $prevConnections = array_values(array_filter($connections, function ($connection) use ($currentNodeId) {
            if (! is_array($connection)) {
                return false;
            }

            // 主流程连接
            if (($connection['target'] ?? null) === $currentNodeId && ($connection['type'] ?? 'main') === 'main') {
                return true;
            }

            // 分支连接（if 的 true/false 分支指向的节点）
            if (($connection['target'] ?? null) === $currentNodeId && ($connection['type'] ?? '') === 'branch') {
                return true;
            }

            return false;
        }));

        if (empty($prevConnections)) {
            // 开始节点没有前置节点
            return ['node_id' => null, 'node_name' => null, 'output' => null];
        }

        // 取第一个前置节点（通常只有一个）
        $prevNodeId = (string) ($prevConnections[0]['source'] ?? '');
        if ($prevNodeId === '') {
            return ['node_id' => null, 'node_name' => null, 'output' => null];
        }

        $prevNode = $nodeMap[$prevNodeId] ?? null;
        $prevNodeName = $prevNode
            ? (string) ($prevNode['name'] ?? $prevNode['nodeName'] ?? $prevNode['type'] ?? '')
            : '';

        // 从 runtime context 中获取上一步的输出
        $prevNodeOutput = $context['runtime']['node_outputs'][$prevNodeId] ?? null;

        return [
            'node_id' => $prevNodeId,
            'node_name' => $prevNodeName,
            'output' => $prevNodeOutput,
        ];
    }

    /**
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
     * @param  array<int, array<string, mixed>>  $connections
     * @param  array<string, int>  $orderMap
     */
    private function resolveNextNodeId(string $currentNodeId, array $connections, array $orderMap): ?string
    {
        $nextConnections = array_values(array_filter($connections, function ($connection) use ($currentNodeId) {
            if (! is_array($connection)) {
                return false;
            }

            if (($connection['source'] ?? null) !== $currentNodeId) {
                return false;
            }

            return ($connection['type'] ?? 'main') !== 'branch';
        }));

        if (empty($nextConnections)) {
            return null;
        }

        usort($nextConnections, function ($left, $right) use ($orderMap) {
            $leftTarget = (string) ($left['target'] ?? '');
            $rightTarget = (string) ($right['target'] ?? '');

            return ($orderMap[$leftTarget] ?? PHP_INT_MAX) <=> ($orderMap[$rightTarget] ?? PHP_INT_MAX);
        });

        $target = (string) ($nextConnections[0]['target'] ?? '');

        return $target !== '' ? $target : null;
    }

    /**
     * @param  array<int, array<string, mixed>>  $connections
     * @param  array<string, int>  $orderMap
     */
    private function resolveBranchNextNodeId(
        string $currentNodeId,
        string $sourcePort,
        array $connections,
        array $orderMap
    ): ?string {
        $expectedPort = strtolower(trim($sourcePort));
        $nextConnections = array_values(array_filter($connections, function ($connection) use ($currentNodeId, $expectedPort) {
            if (! is_array($connection)) {
                return false;
            }

            if (($connection['source'] ?? null) !== $currentNodeId) {
                return false;
            }

            if (($connection['type'] ?? 'main') !== 'branch') {
                return false;
            }

            $connectionPort = strtolower(trim((string) ($connection['sourcePort'] ?? '')));

            return $connectionPort === $expectedPort;
        }));

        if (empty($nextConnections)) {
            return null;
        }

        usort($nextConnections, function ($left, $right) use ($orderMap) {
            $leftTarget = (string) ($left['target'] ?? '');
            $rightTarget = (string) ($right['target'] ?? '');

            return ($orderMap[$leftTarget] ?? PHP_INT_MAX) <=> ($orderMap[$rightTarget] ?? PHP_INT_MAX);
        });

        $target = (string) ($nextConnections[0]['target'] ?? '');

        return $target !== '' ? $target : null;
    }

    /**
     * @param  array<string, mixed>  $parameters
     */
    private function calculateWaitUntil(array $parameters): Carbon
    {
        $mode = (string) ($parameters['mode'] ?? 'after');
        $now = Carbon::now();

        if ($mode === 'at') {
            $time = (string) ($parameters['time'] ?? '');
            if ($time === '') {
                return $now;
            }

            $target = Carbon::parse($time);

            return $target->lte($now) ? $now : $target;
        }

        $delay = max(1, (int) ($parameters['delay'] ?? 1));
        $unit = (string) ($parameters['unit'] ?? 'minutes');

        return match ($unit) {
            'seconds' => $now->copy()->addSeconds($delay),
            'minutes' => $now->copy()->addMinutes($delay),
            'hours' => $now->copy()->addHours($delay),
            'days' => $now->copy()->addDays($delay),
            default => $now->copy()->addMinutes($delay),
        };
    }

    /**
     * @param  array<string, mixed>  $parameters
     * @param  array<string, mixed>  $context
     * @return array{matched: bool, matched_port: string, matched_group_index: int|null}
     */
    private function executeConditionBusinessNode(string $nodeId, array $parameters, array $context): array
    {
        $groups = $parameters['groups'] ?? [];
        if (empty($groups) || ! is_array($groups)) {
            return ['matched' => false, 'matched_port' => 'default', 'matched_group_index' => null];
        }

        $parser = new ParseWorkflowConditionField;

        foreach ($groups as $index => $group) {
            $rules = $group['rules'] ?? [];
            if (empty($rules)) {
                continue;
            }

            if ($parser->evaluateGroup($group, $context, $this->nameMap)) {
                return [
                    'matched' => true,
                    'matched_port' => 'cond_'.($index + 1),
                    'matched_group_index' => $index,
                ];
            }
        }

        return ['matched' => false, 'matched_port' => 'default', 'matched_group_index' => null];
    }

    private function executeLogNode(
        WorkflowExecution $execution,
        string $nodeId,
        array $parameters,
        array $context
    ): array {
        $messageTemplate = (string) ($parameters['message'] ?? "workflow execution {$execution->id} node {$nodeId}");
        $message = $this->renderMessage($messageTemplate, $context);

        return [
            'logged' => true,
            'message' => $message,
        ];
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function renderMessage(string $template, array $context): string
    {
        return $this->contextResolver->renderTemplate($template, $context, $this->nameMap);
    }

    /**
     * @param  array<string, mixed>|null  $output
     */
    private function finishStep(
        WorkflowExecutionStep $step,
        string $status,
        ?array $output,
        ?string $errorMessage,
        Carbon $stepStartAt
    ): void {
        $step->status = $status;
        $step->output_data = $output;
        $step->error_message = $errorMessage;
        $step->finished_at = now();
        $step->duration_ms = $this->normalizeDurationMs(
            $stepStartAt->diffInMilliseconds($step->finished_at, true)
        );
        $step->save();
    }

    /**
     * @param  array<string, mixed>  $context
     * @param  array<string, mixed>|null  $output
     */
    private function appendRuntimeStep(array &$context, string $nodeId, string $nodeType, string $status, ?array $output): void
    {
        $context['runtime']['steps'][] = [
            'node_id' => $nodeId,
            'node_type' => $nodeType,
            'status' => $status,
            'at' => now()->toIso8601String(),
        ];

        if ($nodeId !== '' && is_array($output)) {
            $context['runtime']['node_outputs'][$nodeId] = $output;
        }
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function advanceExecution(WorkflowExecution $execution, array $context, ?string $nextNodeId): void
    {
        $execution->status = 'running';
        $execution->current_node_id = $nextNodeId;
        $execution->next_node_id = null;
        $execution->context_data = $context;
        $execution->lock_version = ((int) $execution->lock_version) + 1;
        $execution->save();
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function completeExecution(WorkflowExecution $execution, array $context): void
    {
        $finishedAt = now();
        $execution->status = 'success';
        $execution->finished_at = $finishedAt;
        $execution->duration = $execution->started_at
            ? $this->normalizeDurationMs(Carbon::parse($execution->started_at)->diffInMilliseconds($finishedAt, true))
            : null;
        $execution->current_node_id = null;
        $execution->next_node_id = null;
        $execution->waiting_until = null;
        $execution->context_data = $context;
        $execution->output_data = $context;
        $execution->lock_version = ((int) $execution->lock_version) + 1;
        $execution->save();
    }

    /**
     * @param  array<string, mixed>|null  $context
     */
    private function failExecution(WorkflowExecution $execution, string $message, ?array $context = null): void
    {
        $finishedAt = now();
        $execution->status = 'error';
        $execution->error_message = $message;
        $execution->finished_at = $finishedAt;
        $execution->duration = $execution->started_at
            ? $this->normalizeDurationMs(Carbon::parse($execution->started_at)->diffInMilliseconds($finishedAt, true))
            : null;
        $execution->waiting_until = null;
        $execution->next_node_id = null;
        if (is_array($context)) {
            $execution->context_data = $context;
        }
        $execution->lock_version = ((int) $execution->lock_version) + 1;
        $execution->save();
    }

    private function nextAttempt(int $executionId, string $nodeId): int
    {
        return WorkflowExecutionStep::query()
            ->where('workflow_execution_id', $executionId)
            ->where('node_id', $nodeId)
            ->count() + 1;
    }

    private function normalizeDurationMs(int|float $value): int
    {
        return max(0, (int) round($value));
    }
}
