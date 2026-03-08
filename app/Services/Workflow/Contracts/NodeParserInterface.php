<?php

namespace App\Services\Workflow\Contracts;

interface NodeParserInterface
{
    /**
     * 解析前端节点数据为 RuleGo 格式
     *
     * @param  array  $node  前端节点数据
     * @return array RuleGo 格式的节点配置
     */
    public function parse(array $node): array;

    /**
     * 验证节点参数是否有效
     *
     * @param  array  $node  前端节点数据
     */
    public function validate(array $node): bool;

    /**
     * 获取该解析器支持的节点类型
     */
    public function getNodeType(): string;
}
