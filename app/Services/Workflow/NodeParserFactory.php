<?php

namespace App\Services\Workflow;

use App\Services\Workflow\Contracts\NodeParserInterface;
use App\Services\Workflow\Parsers\CreateFollowupNodeParser;
use App\Services\Workflow\Parsers\WaitNodeParser;
use InvalidArgumentException;

class NodeParserFactory
{
    /**
     * 解析器映射表
     *
     * @var array<string, string>
     */
    protected static array $parsers = [
        'wait' => WaitNodeParser::class,
        'create_followup' => CreateFollowupNodeParser::class,
        // 后续添加更多节点解析器
        // 'sendMessage' => SendMessageNodeParser::class,
        // 'condition' => ConditionNodeParser::class,
        // 'webhook' => WebhookNodeParser::class,
    ];

    /**
     * 解析器实例缓存
     *
     * @var array<string, NodeParserInterface>
     */
    protected static array $instances = [];

    /**
     * 根据节点类型获取对应的解析器实例
     *
     * @param  string  $nodeType  节点类型
     *
     * @throws InvalidArgumentException
     */
    public static function make(string $nodeType): NodeParserInterface
    {
        // 如果已有实例，直接返回
        if (isset(self::$instances[$nodeType])) {
            return self::$instances[$nodeType];
        }

        // 检查是否支持该节点类型
        if (! isset(self::$parsers[$nodeType])) {
            throw new InvalidArgumentException("不支持的节点类型: {$nodeType}");
        }

        $parserClass = self::$parsers[$nodeType];

        // 创建解析器实例并缓存
        self::$instances[$nodeType] = new $parserClass;

        return self::$instances[$nodeType];
    }

    /**
     * 注册新的节点解析器
     *
     * @param  string  $nodeType  节点类型
     * @param  string  $parserClass  解析器类名
     */
    public static function register(string $nodeType, string $parserClass): void
    {
        self::$parsers[$nodeType] = $parserClass;

        // 清除该类型的缓存实例
        unset(self::$instances[$nodeType]);
    }

    /**
     * 检查是否支持某个节点类型
     *
     * @param  string  $nodeType  节点类型
     */
    public static function supports(string $nodeType): bool
    {
        return isset(self::$parsers[$nodeType]);
    }

    /**
     * 获取所有已注册的节点类型
     *
     * @return array<string>
     */
    public static function getSupportedTypes(): array
    {
        return array_keys(self::$parsers);
    }

    /**
     * 清除所有解析器实例缓存
     */
    public static function clearInstances(): void
    {
        self::$instances = [];
    }
}
