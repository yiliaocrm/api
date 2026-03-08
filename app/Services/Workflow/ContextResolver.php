<?php

namespace App\Services\Workflow;

use RuntimeException;

/**
 * 工作流上下文变量解析服务
 * 支持 n8n 风格的节点名称引用：$('节点名称').field
 */
class ContextResolver
{
    /**
     * 解析 n8n 风格的节点引用或传统 UUID 路径
     *
     * 支持格式：
     * - $('节点名称').field - n8n 风格引用
     * - $("节点名称").field - n8n 风格引用（双引号）
     * - runtime.node_outputs.uuid.field - 传统 UUID 引用
     *
     * @param  string  $path  引用路径
     * @param  array  $context  上下文数据
     * @param  array  $nameMap  节点名称到 UUID 的映射
     * @return mixed 解析后的值
     *
     * @throws RuntimeException 当节点不存在时
     */
    public function resolve(string $path, array $context, array $nameMap = []): mixed
    {
        // 展开节点名称引用为 UUID 路径
        $expandedPath = $this->expandNodeReference($path, $nameMap);

        // 使用 Laravel 的 data_get 获取值
        return data_get($context, $expandedPath);
    }

    /**
     * 渲染模板，支持 {{$('节点名称').field}} 语法
     *
     * @param  string  $template  模板字符串
     * @param  array  $context  上下文数据
     * @param  array  $nameMap  节点名称到 UUID 的映射
     * @return string 渲染后的字符串
     */
    public function renderTemplate(string $template, array $context, array $nameMap = []): string
    {
        $result = preg_replace_callback(
            '/\\{\\{\\s*([^}]+)\\s*\\}\\}/',
            function ($matches) use ($context, $nameMap) {
                $path = trim($matches[1]);
                $value = $this->resolve($path, $context, $nameMap);

                if ($value === null) {
                    return '';
                }

                if (is_scalar($value)) {
                    return (string) $value;
                }

                return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '';
            },
            $template
        );

        return $result ?: $template;
    }

    /**
     * 将节点名称引用展开为 UUID 路径
     *
     * 转换：$('节点名称').field -> runtime.node_outputs.{uuid}.field
     *
     * @param  string  $path  原始路径
     * @param  array  $nameMap  节点名称到 UUID 的映射
     * @return string 展开后的路径
     *
     * @throws RuntimeException 当节点不存在时
     */
    public function expandNodeReference(string $path, array $nameMap = []): string
    {
        // 处理 $('节点名称') 或 $("节点名称") 格式
        return preg_replace_callback(
            '/\\$\\([\'"]([^\'"]+)[\'"]\\)/',
            function ($matches) use ($nameMap) {
                $nodeName = $matches[1];
                $uuid = $nameMap[$nodeName] ?? null;

                if (! $uuid) {
                    throw new RuntimeException("节点 '{$nodeName}' 不存在或未在名称映射中找到");
                }

                return "runtime.node_outputs.{$uuid}";
            },
            $path
        );
    }

    /**
     * 从工作流数据中构建名称映射
     *
     * @param  array  $workflow  工作流数据
     * @return array 节点名称到 UUID 的映射
     */
    public function buildNameMapFromWorkflow(array $workflow): array
    {
        $nameMap = [];

        // 优先使用 meta.name_map
        if (isset($workflow['meta']['name_map']) && is_array($workflow['meta']['name_map'])) {
            return $workflow['meta']['name_map'];
        }

        // 如果没有 name_map，从节点列表构建
        $nodes = $workflow['nodes'] ?? [];
        foreach ($nodes as $node) {
            if (isset($node['name']) && isset($node['id'])) {
                $nameMap[$node['name']] = $node['id'];
            }
        }

        return $nameMap;
    }
}
