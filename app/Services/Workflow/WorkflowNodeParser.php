<?php

namespace App\Services\Workflow;

use Exception;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class WorkflowNodeParser
{
    /**
     * 解析前端工作流节点数组为 RuleGo 规则链格式
     *
     * @param  array  $nodes  前端节点数组
     * @return array RuleGo 规则链节点数组
     *
     * @throws Exception
     */
    public function parseNodes(array $nodes): array
    {
        $ruleChainNodes = [];

        foreach ($nodes as $node) {
            try {
                $parsedNode = $this->parseNode($node);
                if ($parsedNode !== null) {
                    $ruleChainNodes[] = $parsedNode;
                }
            } catch (Exception $e) {
                // 记录错误日志
                Log::error('工作流节点解析失败', [
                    'node' => $node,
                    'error' => $e->getMessage(),
                ]);

                // 根据业务需求决定是抛出异常还是跳过该节点
                throw new Exception("节点解析失败: {$e->getMessage()}", 0, $e);
            }
        }

        return $ruleChainNodes;
    }

    /**
     * 解析单个节点
     *
     * @param  array  $node  前端节点数据
     * @return array|null RuleGo 格式的节点配置，如果节点类型不支持则返回 null
     *
     * @throws InvalidArgumentException
     */
    public function parseNode(array $node): ?array
    {
        $nodeType = $node['type'] ?? null;

        if (empty($nodeType)) {
            throw new InvalidArgumentException('节点缺少 type 字段');
        }

        // 检查是否支持该节点类型
        if (! NodeParserFactory::supports($nodeType)) {
            Log::warning("不支持的节点类型: {$nodeType}，跳过解析", ['node' => $node]);

            return null;
        }

        // 获取对应的解析器
        $parser = NodeParserFactory::make($nodeType);

        // 验证节点参数
        if (! $parser->validate($node)) {
            throw new InvalidArgumentException("节点参数验证失败: {$nodeType}");
        }

        // 解析节点
        $parsedNode = $parser->parse($node);

        // 添加节点 ID（RuleGo 需要）
        $parsedNode['id'] = $node['id'] ?? $this->generateNodeId();

        return $parsedNode;
    }

    /**
     * 生成节点 ID
     */
    protected function generateNodeId(): string
    {
        return uniqid('node_', true);
    }

    /**
     * 验证所有节点是否有效
     *
     * @param  array  $nodes  前端节点数组
     * @return array 返回验证结果 ['valid' => bool, 'errors' => array]
     */
    public function validateNodes(array $nodes): array
    {
        $errors = [];

        foreach ($nodes as $index => $node) {
            $nodeType = $node['type'] ?? null;

            if (empty($nodeType)) {
                $errors[] = "节点 #{$index}: 缺少 type 字段";

                continue;
            }

            if (! NodeParserFactory::supports($nodeType)) {
                $errors[] = "节点 #{$index}: 不支持的节点类型 '{$nodeType}'";

                continue;
            }

            try {
                $parser = NodeParserFactory::make($nodeType);
                if (! $parser->validate($node)) {
                    $errors[] = "节点 #{$index}: 参数验证失败";
                }
            } catch (Exception $e) {
                $errors[] = "节点 #{$index}: {$e->getMessage()}";
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }
}
