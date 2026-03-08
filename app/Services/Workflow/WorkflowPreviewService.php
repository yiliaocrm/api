<?php

namespace App\Services\Workflow;

use Carbon\Carbon;
use Illuminate\Support\Str;
use RuntimeException;

class WorkflowPreviewService
{
    private ContextResolver $contextResolver;

    /**
     * @var array<string, string>
     */
    private array $nameMap = [];

    public function __construct()
    {
        $this->contextResolver = new ContextResolver;
    }

    /**
     * 基于上游节点输出自动推断预览输入
     * 收集所有上游节点 preview_schemas 中的 output_sample 并进行合并
     *
     * @param  array<string, mixed>  $ruleChain
     * @return array<string, mixed>
     */
    public function inferPreviewInput(array $ruleChain, string $nodeId): array
    {
        $upstreamNodeIds = $this->findAllUpstreamNodes($ruleChain, $nodeId);
        $previewSchemas = is_array($ruleChain['meta']['preview_schemas'] ?? null)
            ? $ruleChain['meta']['preview_schemas']
            : [];

        $merged = [];
        foreach ($upstreamNodeIds as $upstreamId) {
            $schema = $previewSchemas[$upstreamId] ?? null;
            if (is_array($schema) && isset($schema['output_sample']) && is_array($schema['output_sample'])) {
                $merged = array_merge($merged, $schema['output_sample']);
            }
        }

        return $merged;
    }

    /**
     * 按拓扑顺序批量预览多个节点
     * 返回所有节点的预览结果数组
     *
     * @param  array<string, mixed>  $ruleChain
     * @param  array<int, string>|null  $nodeIds
     * @return array<string, mixed>
     */
    public function batchPreviewNodes(array $ruleChain, ?array $nodeIds = null): array
    {
        $nodes = $ruleChain['nodes'] ?? [];
        $connections = $ruleChain['connections'] ?? [];

        // 构建依赖图
        $graph = $this->buildDependencyGraph($nodes, $connections);

        // 拓扑排序
        $sortedNodeIds = $this->topologicalSort($graph);

        // 如果指定了节点，则只保留指定节点
        if ($nodeIds !== null) {
            $sortedNodeIds = array_values(array_intersect($sortedNodeIds, $nodeIds));
        }

        $results = [];
        $previewSchemas = $ruleChain['meta']['preview_schemas'] ?? [];

        foreach ($sortedNodeIds as $nodeId) {
            try {
                // 基于已有结果自动推断输入
                $inferredInput = $this->inferPreviewInput($ruleChain, $nodeId);

                // 执行预览
                $result = $this->previewNode($ruleChain, $nodeId, $inferredInput);

                // 存入预览 schemas
                $previewSchemas[$nodeId] = [
                    'node_id' => $result['node_id'],
                    'node_type' => $result['node_type'],
                    'node_name' => $result['node_name'],
                    'output_schema' => $result['output_schema'],
                    'output_sample' => $result['output_data'],
                    'input_data' => $inferredInput,
                    'upstream_node_ids' => $this->findAllUpstreamNodes($ruleChain, $nodeId),
                    'updated_at' => now()->toIso8601String(),
                ];

                $results[] = [
                    'node_id' => $nodeId,
                    'status' => 'success',
                    'preview_schema_entry' => $previewSchemas[$nodeId],
                ];

                // 更新 rule_chain 供下一次迭代使用
                $ruleChain['meta']['preview_schemas'] = $previewSchemas;

            } catch (\Exception $e) {
                $results[] = [
                    'node_id' => $nodeId,
                    'status' => 'failed',
                    'error' => $e->getMessage(),
                ];
            }
        }

        return [
            'total' => count($sortedNodeIds),
            'succeeded' => count(array_filter($results, fn ($r) => $r['status'] === 'success')),
            'failed' => count(array_filter($results, fn ($r) => $r['status'] === 'failed')),
            'results' => $results,
            'preview_schemas' => $previewSchemas,
        ];
    }

    /**
     * 查找受变更影响的所有下游节点
     * 用于级联失效处理
     *
     * @param  array<string, mixed>  $ruleChain
     * @return array<int, string>
     */
    public function findDownstreamNodes(array $ruleChain, string $nodeId): array
    {
        $connections = $ruleChain['connections'] ?? [];
        $downstream = [];
        $queue = [$nodeId];
        $visited = [];

        while (! empty($queue)) {
            $currentId = array_shift($queue);
            if (isset($visited[$currentId])) {
                continue;
            }
            $visited[$currentId] = true;

            foreach ($connections as $conn) {
                $source = (string) ($conn['source'] ?? '');
                $target = (string) ($conn['target'] ?? '');

                if ($source === $currentId && ! isset($visited[$target])) {
                    $downstream[] = $target;
                    $queue[] = $target;
                }
            }
        }

        return array_values(array_unique($downstream));
    }

    /**
     * @param  array<string, mixed>  $ruleChain
     * @param  array<string, mixed>  $previewPayload
     * @return array{
     *     node_id: string,
     *     node_type: string,
     *     node_name: string,
     *     output_data: array<string, mixed>,
     *     output_schema: array<int, array<string, mixed>>,
     *     context_data: array<string, mixed>
     * }
     */
    public function previewNode(array $ruleChain, string $nodeId, array $previewPayload = []): array
    {
        $nodes = is_array($ruleChain['nodes'] ?? null) ? $ruleChain['nodes'] : [];
        if (empty($nodes)) {
            throw new RuntimeException('规则链缺少节点');
        }

        $this->nameMap = $this->contextResolver->buildNameMapFromWorkflow($ruleChain);

        $targetNode = null;
        foreach ($nodes as $node) {
            if ((string) ($node['id'] ?? '') === $nodeId) {
                $targetNode = $node;
                break;
            }
        }

        if (! is_array($targetNode)) {
            throw new RuntimeException("未找到目标节点 [{$nodeId}]");
        }

        $nodeType = strtolower((string) ($targetNode['type'] ?? ''));
        $nodeName = (string) ($targetNode['name'] ?? $targetNode['nodeName'] ?? $nodeType);
        $parameters = $this->extractParameters($targetNode);
        $runtimeNodeOutputs = $this->buildPreviewRuntimeNodeOutputs($ruleChain, $nodeId);

        $context = [
            'trigger' => [
                'event' => 'preview.manual',
                'model_type' => 'preview',
                'model_id' => 'manual',
                'triggered_at' => now()->toIso8601String(),
            ],
            'payload' => $previewPayload,
            'runtime' => [
                'steps' => [],
                'node_outputs' => $runtimeNodeOutputs,
            ],
        ];

        $output = match ($nodeType) {
            'start_trigger' => $this->previewStartTriggerNode($parameters, $context),
            'start_periodic' => $this->previewStartPeriodicNode($parameters, $context),
            'wait' => $this->previewWaitNode($parameters),
            'create_followup' => $this->previewCreateFollowupNode($parameters, $context),
            'log' => $this->previewLogNode($parameters, $context),
            'end' => ['ended' => true],
            default => throw new RuntimeException("节点类型 [{$nodeType}] 当前版本暂不支持预览"),
        };

        $context['runtime']['steps'][] = [
            'node_id' => $nodeId,
            'node_type' => $nodeType,
            'status' => 'success',
            'output' => $output,
            'at' => now()->toIso8601String(),
        ];
        $context['runtime']['node_outputs'][$nodeId] = $output;

        return [
            'node_id' => $nodeId,
            'node_type' => $nodeType,
            'node_name' => $nodeName,
            'output_data' => $output,
            'output_schema' => $this->inferOutputSchemaEnhanced($output),
            'context_data' => $context,
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
     * @param  array<string, mixed>  $parameters
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    private function previewStartTriggerNode(array $parameters, array $context): array
    {
        $triggerEvents = $parameters['triggerEvents'] ?? $parameters['triggerEventsText'] ?? [];
        $events = is_array($triggerEvents)
            ? array_values(array_filter(array_map('strval', $triggerEvents)))
            : array_values(array_filter(array_map('trim', explode(',', (string) $triggerEvents))));

        return [
            'started' => true,
            'trigger' => $context['trigger'] ?? null,
            'trigger_events' => $events,
            'payload' => $context['payload'] ?? [],
        ];
    }

    /**
     * 周期型开始节点预览
     *
     * @param  array<string, mixed>  $parameters
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    private function previewStartPeriodicNode(array $parameters, array $context): array
    {
        return [
            'started' => true,
            'trigger' => [
                'event' => 'periodic.scheduled',
                'type' => 'periodic',
            ],
            'periodic_config' => [
                'runTime' => $parameters['runTime'] ?? 'day',
                'dayInterval' => $parameters['dayInterval'] ?? null,
                'weekInterval' => $parameters['weekInterval'] ?? null,
                'weekDays' => $parameters['weekDays'] ?? [],
                'monthInterval' => $parameters['monthInterval'] ?? null,
                'monthDays' => $parameters['monthDays'] ?? [],
                'executeTime' => $parameters['executeTime'] ?? '09:00',
            ],
            'payload' => $context['payload'] ?? [],
        ];
    }

    /**
     * @param  array<string, mixed>  $parameters
     * @return array<string, mixed>
     */
    private function previewWaitNode(array $parameters): array
    {
        $waitingUntil = $this->calculateWaitUntil($parameters);

        return [
            'waiting_until' => $waitingUntil->toIso8601String(),
        ];
    }

    /**
     * @param  array<string, mixed>  $parameters
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    private function previewLogNode(array $parameters, array $context): array
    {
        $messageTemplate = (string) ($parameters['message'] ?? '');
        $message = $this->renderMessage($messageTemplate, $context);

        return [
            'logged' => true,
            'message' => $message,
        ];
    }

    /**
     * @param  array<string, mixed>  $parameters
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    private function previewCreateFollowupNode(array $parameters, array $context): array
    {
        $this->assertCreateFollowupParameters($parameters);

        $customerId = $context['payload']['id'] ?? $context['customer_id'] ?? null;
        if (! $customerId) {
            throw new RuntimeException('create_followup 节点预览缺少客户ID，请在预览输入中提供 payload.id');
        }

        return [
            'followup_id' => 'preview-'.Str::uuid(),
            'created' => true,
            'followup_date' => $this->calculateFollowupDate($parameters),
            'customer_id' => (string) $customerId,
        ];
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
     */
    private function calculateFollowupDate(array $parameters): string
    {
        $dateMode = strtolower(trim((string) ($parameters['date_mode'] ?? 'relative')));
        if ($dateMode === 'absolute') {
            return Carbon::parse((string) $parameters['absolute_date'])->toDateString();
        }

        $offset = max(1, (int) ($parameters['date_offset'] ?? 1));
        $unit = strtolower(trim((string) ($parameters['date_unit'] ?? 'days')));

        return match ($unit) {
            'hours' => now()->addHours($offset)->toDateString(),
            'days' => now()->addDays($offset)->toDateString(),
            'weeks' => now()->addWeeks($offset)->toDateString(),
            default => now()->addDays($offset)->toDateString(),
        };
    }

    /**
     * @param  array<string, mixed>  $parameters
     */
    private function assertCreateFollowupParameters(array $parameters): void
    {
        if (trim((string) ($parameters['title'] ?? '')) === '') {
            throw new RuntimeException('create_followup 节点缺少回访标题');
        }

        if (empty($parameters['type'])) {
            throw new RuntimeException('create_followup 节点缺少回访类型');
        }

        if (empty($parameters['tool'])) {
            throw new RuntimeException('create_followup 节点缺少回访工具');
        }

        if (empty($parameters['followup_user'])) {
            throw new RuntimeException('create_followup 节点缺少提醒人员');
        }

        $dateMode = strtolower(trim((string) ($parameters['date_mode'] ?? 'relative')));
        if ($dateMode === 'relative') {
            if ((int) ($parameters['date_offset'] ?? 0) < 1) {
                throw new RuntimeException('create_followup 节点相对时间偏移必须大于 0');
            }

            $dateUnit = strtolower(trim((string) ($parameters['date_unit'] ?? '')));
            if (! in_array($dateUnit, ['hours', 'days', 'weeks'], true)) {
                throw new RuntimeException('create_followup 节点时间单位无效');
            }

            return;
        }

        if ($dateMode === 'absolute') {
            if (trim((string) ($parameters['absolute_date'] ?? '')) === '') {
                throw new RuntimeException('create_followup 节点缺少绝对时间');
            }

            return;
        }

        throw new RuntimeException('create_followup 节点日期模式无效');
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function renderMessage(string $template, array $context): string
    {
        return $this->contextResolver->renderTemplate($template, $context, $this->nameMap);
    }

    /**
     * @param  array<string, mixed>  $output
     * @return array<int, array<string, mixed>>
     */
    private function inferOutputSchema(array $output): array
    {
        $schema = [];

        $walker = function (string $prefix, mixed $value) use (&$schema, &$walker): void {
            if (is_array($value)) {
                if ($this->isAssocArray($value)) {
                    if ($prefix !== '') {
                        $schema[] = [
                            'field' => $prefix,
                            'type' => 'object',
                            'example' => $value,
                        ];
                    }
                    foreach ($value as $childKey => $childValue) {
                        $nextPrefix = $prefix === '' ? (string) $childKey : "{$prefix}.{$childKey}";
                        $walker($nextPrefix, $childValue);
                    }

                    return;
                }

                $schema[] = [
                    'field' => $prefix,
                    'type' => 'array',
                    'example' => $value,
                ];

                return;
            }

            $schema[] = [
                'field' => $prefix,
                'type' => $this->detectValueType($value),
                'example' => $value,
            ];
        };

        foreach ($output as $key => $value) {
            $walker((string) $key, $value);
        }

        return array_values(array_filter($schema, fn ($item) => (string) ($item['field'] ?? '') !== ''));
    }

    private function detectValueType(mixed $value): string
    {
        return match (true) {
            is_bool($value) => 'boolean',
            is_int($value) => 'integer',
            is_float($value) => 'number',
            is_array($value) => $this->isAssocArray($value) ? 'object' : 'array',
            $value === null => 'null',
            default => 'string',
        };
    }

    /**
     * @param  array<mixed>  $value
     */
    private function isAssocArray(array $value): bool
    {
        if ($value === []) {
            return false;
        }

        return array_keys($value) !== range(0, count($value) - 1);
    }

    /**
     * 增强版 Schema 推断，支持 n8n 类型和嵌套结构
     * 为嵌套对象/数组增加 n8n_type 字段和 children 数组
     *
     * @param  array<string, mixed>  $output
     * @return array<int, array<string, mixed>>
     */
    private function inferOutputSchemaEnhanced(array $output): array
    {
        $schema = [];

        $walker = function (string $prefix, mixed $value, int $depth = 0) use (&$schema, &$walker): void {
            // 限制深度，避免过度嵌套
            if ($depth > 5) {
                return;
            }

            if (is_array($value)) {
                if ($this->isAssocArray($value)) {
                    $children = [];
                    foreach ($value as $childKey => $childValue) {
                        $nextPrefix = $prefix === '' ? (string) $childKey : "{$prefix}.{$childKey}";
                        $childSchema = $this->buildSchemaEntry($nextPrefix, $childValue, $depth + 1);
                        $children[] = $childSchema;
                    }

                    if ($prefix !== '') {
                        $schema[] = [
                            'field' => $prefix,
                            'type' => 'object',
                            'n8n_type' => 'object',
                            'example' => $value,
                            'children' => $children,
                        ];
                    } else {
                        // 根层级：直接追加子字段
                        foreach ($children as $child) {
                            $schema[] = $child;
                        }
                    }

                    return;
                }

                $schema[] = [
                    'field' => $prefix,
                    'type' => 'array',
                    'n8n_type' => 'array',
                    'example' => $value,
                    'children' => [],
                ];

                return;
            }

            $schema[] = $this->buildSchemaEntry($prefix, $value, $depth);
        };

        foreach ($output as $key => $value) {
            $walker((string) $key, $value, 0);
        }

        return array_values(array_filter($schema, fn ($item) => (string) ($item['field'] ?? '') !== ''));
    }

    /**
     * 构建单个包含 n8n 类型的 Schema 条目
     *
     * @return array<string, mixed>
     */
    private function buildSchemaEntry(string $field, mixed $value, int $depth): array
    {
        $type = $this->detectValueType($value);
        $n8nType = $this->mapToN8nType($type);

        $entry = [
            'field' => $field,
            'type' => $type,
            'n8n_type' => $n8nType,
            'example' => $value,
        ];

        // 为嵌套结构补充 children
        if (is_array($value) && $this->isAssocArray($value) && $depth < 5) {
            $children = [];
            foreach ($value as $childKey => $childValue) {
                $nextPrefix = "{$field}.{$childKey}";
                $children[] = $this->buildSchemaEntry($nextPrefix, $childValue, $depth + 1);
            }
            $entry['children'] = $children;
        } else {
            $entry['children'] = [];
        }

        return $entry;
    }

    /**
     * 将 PHP 类型映射为 n8n 展示类型
     */
    private function mapToN8nType(string $type): string
    {
        return match ($type) {
            'string' => 'string',
            'integer', 'number' => 'number',
            'boolean' => 'boolean',
            'array' => 'array',
            'object' => 'object',
            'null' => 'null',
            default => 'string',
        };
    }

    /**
     * 查找指定节点的所有上游节点
     *
     * @param  array<string, mixed>  $ruleChain
     * @return array<int, string>
     */
    public function findUpstreamNodes(array $ruleChain, string $nodeId): array
    {
        return $this->findAllUpstreamNodes($ruleChain, $nodeId);
    }

    /**
     * @param  array<string, mixed>  $ruleChain
     * @return array<string, array<string, mixed>>
     */
    private function previewSchemaMap(array $ruleChain): array
    {
        $schemas = $ruleChain['meta']['preview_schemas'] ?? [];

        return is_array($schemas) ? $schemas : [];
    }

    /**
     * @param  array<string, mixed>  $ruleChain
     * @return array<string, int>
     */
    private function resolveNodeOrderMap(array $ruleChain): array
    {
        $flow = is_array($ruleChain['layout']['flow'] ?? null) ? $ruleChain['layout']['flow'] : [];
        $orderMap = [];

        foreach ($flow as $item) {
            if (! is_array($item)) {
                continue;
            }

            $nodeId = (string) ($item['nodeId'] ?? '');
            if ($nodeId === '') {
                continue;
            }

            $orderMap[$nodeId] = (int) ($item['order'] ?? 0);
        }

        return $orderMap;
    }

    /**
     * @param  array<string, mixed>  $ruleChain
     * @return array<int, string>
     */
    private function findAllUpstreamNodes(array $ruleChain, string $nodeId): array
    {
        $normalizedNodeId = trim($nodeId);
        if ($normalizedNodeId === '') {
            return [];
        }

        $connections = is_array($ruleChain['connections'] ?? null) ? $ruleChain['connections'] : [];
        $incomingMap = [];
        foreach ($connections as $connection) {
            if (! is_array($connection)) {
                continue;
            }

            $source = (string) ($connection['source'] ?? '');
            $target = (string) ($connection['target'] ?? '');
            if ($source === '' || $target === '') {
                continue;
            }

            if (! isset($incomingMap[$target])) {
                $incomingMap[$target] = [];
            }
            $incomingMap[$target][] = $source;
        }

        $visited = [];
        $stack = [$normalizedNodeId];
        while (! empty($stack)) {
            $current = array_pop($stack);
            $incoming = $incomingMap[$current] ?? [];
            foreach ($incoming as $sourceId) {
                if (isset($visited[$sourceId])) {
                    continue;
                }
                $visited[$sourceId] = true;
                $stack[] = $sourceId;
            }
        }

        $upstreamNodeIds = array_keys($visited);
        $orderMap = $this->resolveNodeOrderMap($ruleChain);
        usort($upstreamNodeIds, function ($left, $right) use ($orderMap) {
            $orderDiff = ($orderMap[$left] ?? PHP_INT_MAX) <=> ($orderMap[$right] ?? PHP_INT_MAX);
            if ($orderDiff !== 0) {
                return $orderDiff;
            }

            return strcmp($left, $right);
        });

        return array_values($upstreamNodeIds);
    }

    /**
     * @param  array<string, mixed>  $ruleChain
     * @return array<string, array<string, mixed>>
     */
    private function buildPreviewRuntimeNodeOutputs(array $ruleChain, string $nodeId): array
    {
        $previewSchemas = $this->previewSchemaMap($ruleChain);
        $upstreamNodeIds = $this->findAllUpstreamNodes($ruleChain, $nodeId);
        $nodeOutputs = [];

        foreach ($upstreamNodeIds as $upstreamNodeId) {
            $entry = $previewSchemas[$upstreamNodeId] ?? null;
            $outputSample = is_array($entry) ? ($entry['output_sample'] ?? null) : null;
            if (! is_array($outputSample)) {
                continue;
            }

            $nodeOutputs[$upstreamNodeId] = $outputSample;
        }

        return $nodeOutputs;
    }

    /**
     * 根据节点与连接关系构建依赖图
     *
     * @param  array<int, array<string, mixed>>  $nodes
     * @param  array<int, array<string, mixed>>  $connections
     * @return array<string, array<int, string>>
     */
    private function buildDependencyGraph(array $nodes, array $connections): array
    {
        $graph = [];

        // 初始化所有节点
        foreach ($nodes as $node) {
            $nodeId = (string) ($node['id'] ?? '');
            if ($nodeId !== '') {
                $graph[$nodeId] = [];
            }
        }

        // 添加边（依赖关系）
        foreach ($connections as $conn) {
            $source = (string) ($conn['source'] ?? '');
            $target = (string) ($conn['target'] ?? '');

            if ($source !== '' && $target !== '' && isset($graph[$target])) {
                $graph[$target][] = $source;
            }
        }

        return $graph;
    }

    /**
     * 使用 Kahn 算法进行拓扑排序
     *
     * @param  array<string, array<int, string>>  $graph
     * @return array<int, string>
     */
    private function topologicalSort(array $graph): array
    {
        $inDegree = [];
        $sorted = [];

        // 计算入度
        foreach ($graph as $node => $dependencies) {
            if (! isset($inDegree[$node])) {
                $inDegree[$node] = 0;
            }
            $inDegree[$node] += count($dependencies);

            foreach ($dependencies as $dep) {
                if (! isset($inDegree[$dep])) {
                    $inDegree[$dep] = 0;
                }
            }
        }

        // 找出无依赖的节点
        $queue = [];
        foreach ($inDegree as $node => $degree) {
            if ($degree === 0) {
                $queue[] = $node;
            }
        }

        // 处理队列
        while (! empty($queue)) {
            $current = array_shift($queue);
            $sorted[] = $current;

            // 查找依赖当前节点的节点
            foreach ($graph as $node => $dependencies) {
                if (in_array($current, $dependencies, true)) {
                    $inDegree[$node]--;
                    if ($inDegree[$node] === 0) {
                        $queue[] = $node;
                    }
                }
            }
        }

        // 检查是否存在环
        if (count($sorted) !== count($graph)) {
            throw new RuntimeException('工作流存在循环依赖，无法执行批量预览');
        }

        return $sorted;
    }
}
