<?php

namespace Tests\Unit\Services\Workflow;

use App\Services\Workflow\NodeParserFactory;
use App\Services\Workflow\Parsers\WaitNodeParser;
use InvalidArgumentException;
use Tests\TestCase;

class NodeParserFactoryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // 清除缓存的实例
        NodeParserFactory::clearInstances();
    }

    /**
     * 测试创建已注册的解析器
     */
    public function test_make_returns_parser_instance_for_registered_type()
    {
        $parser = NodeParserFactory::make('wait');

        $this->assertInstanceOf(WaitNodeParser::class, $parser);
    }

    /**
     * 测试创建未注册的解析器抛出异常
     */
    public function test_make_throws_exception_for_unregistered_type()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('不支持的节点类型: unknownType');

        NodeParserFactory::make('unknownType');
    }

    /**
     * 测试解析器实例被缓存
     */
    public function test_parser_instances_are_cached()
    {
        $parser1 = NodeParserFactory::make('wait');
        $parser2 = NodeParserFactory::make('wait');

        $this->assertSame($parser1, $parser2);
    }

    /**
     * 测试检查支持的节点类型
     */
    public function test_supports_returns_true_for_registered_type()
    {
        $this->assertTrue(NodeParserFactory::supports('wait'));
    }

    /**
     * 测试检查不支持的节点类型
     */
    public function test_supports_returns_false_for_unregistered_type()
    {
        $this->assertFalse(NodeParserFactory::supports('unknownType'));
    }

    /**
     * 测试获取所有支持的节点类型
     */
    public function test_get_supported_types_returns_array_of_types()
    {
        $types = NodeParserFactory::getSupportedTypes();

        $this->assertIsArray($types);
        $this->assertContains('wait', $types);
    }

    /**
     * 测试动态注册新的解析器
     */
    public function test_register_adds_new_parser_type()
    {
        // 创建一个测试解析器类
        $testParserClass = new class implements \App\Services\Workflow\Contracts\NodeParserInterface
        {
            public function parse(array $node): array
            {
                return ['type' => 'test'];
            }

            public function validate(array $node): bool
            {
                return true;
            }

            public function getNodeType(): string
            {
                return 'test';
            }
        };

        // 注册新类型
        NodeParserFactory::register('testType', get_class($testParserClass));

        // 验证已注册
        $this->assertTrue(NodeParserFactory::supports('testType'));
        $this->assertContains('testType', NodeParserFactory::getSupportedTypes());
    }

    /**
     * 测试重新注册会清除缓存
     */
    public function test_register_clears_cached_instance()
    {
        // 创建第一个实例
        $parser1 = NodeParserFactory::make('wait');

        // 重新注册相同类型
        NodeParserFactory::register('wait', WaitNodeParser::class);

        // 创建第二个实例
        $parser2 = NodeParserFactory::make('wait');

        // 应该是不同的实例
        $this->assertNotSame($parser1, $parser2);
    }

    /**
     * 测试清除所有实例缓存
     */
    public function test_clear_instances_removes_all_cached_parsers()
    {
        // 创建一些实例
        $parser1 = NodeParserFactory::make('wait');

        // 清除缓存
        NodeParserFactory::clearInstances();

        // 再次创建
        $parser2 = NodeParserFactory::make('wait');

        // 应该是不同的实例
        $this->assertNotSame($parser1, $parser2);
    }
}
