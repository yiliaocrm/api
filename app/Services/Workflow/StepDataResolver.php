<?php

namespace App\Services\Workflow;

use App\Models\WorkflowExecution;
use App\Models\WorkflowExecutionStep;

/**
 * 步骤数据解析服务
 *
 * 提供按需加载步骤输入输出的能力，解决数据冗余存储问题。
 * 重构后：
 * - input_data 只存储引用信息 (from_node_id, from_node_name)
 * - 实际数据从 context.runtime.node_outputs 中获取
 */
class StepDataResolver
{
    /**
     * 获取步骤输入数据
     *
     * 通过 from_node_id 引用解析实际数据，从执行上下文中获取上游节点的输出
     *
     * @return array{parameters: array, from_node_id: ?string, from_node_name: ?string, data: ?array}
     */
    public function getStepInput(WorkflowExecutionStep $step): array
    {
        $inputData = is_array($step->input_data) ? $step->input_data : [];

        $result = [
            'parameters' => $inputData['parameters'] ?? [],
            'from_node_id' => $inputData['from_node_id'] ?? null,
            'from_node_name' => $inputData['from_node_name'] ?? null,
            'data' => null,
        ];

        // 如果有 from_node_id，尝试从上下文中获取实际数据
        $fromNodeId = $result['from_node_id'];
        if ($fromNodeId) {
            $result['data'] = $this->resolveInputDataFromContext($step, $fromNodeId);
        }

        return $result;
    }

    /**
     * 获取步骤输出数据
     *
     * @return array|null 步骤执行的实际输出
     */
    public function getStepOutput(WorkflowExecutionStep $step): ?array
    {
        return is_array($step->output_data) ? $step->output_data : null;
    }

    /**
     * 组合完整输入数据（包含上游节点的实际输出）
     *
     * 返回与重构前相同格式的数据结构，方便兼容现有代码
     *
     * @return array{parameters: array, from_node_id: ?string, from_node_name: ?string, data: ?array}
     */
    public function resolveInputData(WorkflowExecutionStep $step, ?array $context = null): array
    {
        $inputData = $this->getStepInput($step);

        // 如果没有提供 context，尝试从执行记录中获取
        if ($context === null) {
            $context = $this->getExecutionContext($step);
        }

        // 如果仍然没有上下文数据，直接返回已解析的 input_data
        if (! $context || ! is_array($context)) {
            return $inputData;
        }

        // 尝试从上下文的 node_outputs 中获取数据
        $fromNodeId = $inputData['from_node_id'];
        if ($fromNodeId && ! $inputData['data']) {
            $nodeOutputs = $context['runtime']['node_outputs'] ?? [];
            $inputData['data'] = $nodeOutputs[$fromNodeId] ?? null;
        }

        return $inputData;
    }

    /**
     * 从上下文中解析上游节点的输出数据
     *
     * @return array|null 上游节点的输出数据
     */
    private function resolveInputDataFromContext(WorkflowExecutionStep $step, string $fromNodeId): ?array
    {
        $context = $this->getExecutionContext($step);

        if (! $context || ! is_array($context)) {
            return null;
        }

        $nodeOutputs = $context['runtime']['node_outputs'] ?? [];

        return $nodeOutputs[$fromNodeId] ?? null;
    }

    /**
     * 获取执行记录的上下文数据
     */
    private function getExecutionContext(WorkflowExecutionStep $step): ?array
    {
        $execution = $step->execution;

        if (! $execution instanceof WorkflowExecution) {
            return null;
        }

        $contextData = $execution->context_data;

        return is_array($contextData) ? $contextData : null;
    }

    /**
     * 批量获取多个步骤的输入数据
     *
     * @param  iterable<WorkflowExecutionStep>  $steps
     * @return array<int, array{parameters: array, from_node_id: ?string, from_node_name: ?string, data: ?array}>
     */
    public function getBatchInputs(iterable $steps): array
    {
        $result = [];
        $contextCache = null;

        foreach ($steps as $step) {
            // 复用上下文数据，避免重复查询
            if ($contextCache === null) {
                $execution = $step->execution;
                if ($execution instanceof WorkflowExecution) {
                    $contextCache = $execution->context_data;
                }
            }

            $inputData = is_array($step->input_data) ? $step->input_data : [];

            $item = [
                'parameters' => $inputData['parameters'] ?? [],
                'from_node_id' => $inputData['from_node_id'] ?? null,
                'from_node_name' => $inputData['from_node_name'] ?? null,
                'data' => null,
            ];

            // 从缓存的上下文中获取数据
            $fromNodeId = $item['from_node_id'];
            if ($fromNodeId && $contextCache && is_array($contextCache)) {
                $nodeOutputs = $contextCache['runtime']['node_outputs'] ?? [];
                $item['data'] = $nodeOutputs[$fromNodeId] ?? null;
            }

            $result[$step->id] = $item;
        }

        return $result;
    }

    /**
     * 批量获取多个步骤的输出数据
     *
     * @param  iterable<WorkflowExecutionStep>  $steps
     * @return array<int, array|null>
     */
    public function getBatchOutputs(iterable $steps): array
    {
        $result = [];

        foreach ($steps as $step) {
            $result[$step->id] = is_array($step->output_data) ? $step->output_data : null;
        }

        return $result;
    }
}
