<?php

namespace Tests\Unit\Services\Workflow;

use App\Services\Workflow\WorkflowNodeParser;
use Exception;
use InvalidArgumentException;
use Tests\TestCase;

class WorkflowNodeParserTest extends TestCase
{
    protected WorkflowNodeParser $parser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new WorkflowNodeParser;
    }

    /**
     * 测试解析单个有效节点
     */
    public function test_parse_node_returns_parsed_data_for_valid_node()
    {
        $node = [
            'id' => 'test-node-1',
            'type' => 'wait',
            'nodeName' => '等待',
            'parameters' => [
                'mode' => 'after',
                'delay' => 10,
                'unit' => 'minutes',
            ],
        ];

        $result = $this->parser->parseNode($node);

        $this->assertIsArray($result);
        $this->assertEquals('test-node-1', $result['id']);
        $this->assertEquals('x/delay', $result['type']);
        $this->assertArrayHasKey('configuration', $result);
    }

    /**
     * 测试解析缺少 type 字段的节点抛出异常
     */
    public function test_parse_node_throws_exception_for_missing_type()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('节点缺少 type 字段');

        $node = [
            'id' => 'test-node-1',
            'parameters' => [],
        ];

        $this->parser->parseNode($node);
    }

    /**
     * 测试解析不支持的节点类型返回 null
     */
    public function test_parse_node_returns_null_for_unsupported_type()
    {
        $node = [
            'id' => 'test-node-1',
            'type' => 'unsupportedType',
            'parameters' => [],
        ];

        $result = $this->parser->parseNode($node);

        $this->assertNull($result);
    }

    /**
     * 测试解析参数无效的节点抛出异常
     */
    public function test_parse_node_throws_exception_for_invalid_parameters()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('节点参数验证失败');

        $node = [
            'id' => 'test-node-1',
            'type' => 'wait',
            'parameters' => [], // 缺少必要参数
        ];

        $this->parser->parseNode($node);
    }

    /**
     * 测试解析多个节点
     */
    public function test_parse_nodes_returns_array_of_parsed_nodes()
    {
        $nodes = [
            [
                'id' => 'node-1',
                'type' => 'wait',
                'nodeName' => '等待1',
                'parameters' => [
                    'mode' => 'after',
                    'delay' => 10,
                    'unit' => 'minutes',
                ],
            ],
            [
                'id' => 'node-2',
                'type' => 'wait',
                'nodeName' => '等待2',
                'parameters' => [
                    'mode' => 'after',
                    'delay' => 20,
                    'unit' => 'minutes',
                ],
            ],
        ];

        $results = $this->parser->parseNodes($nodes);

        $this->assertIsArray($results);
        $this->assertCount(2, $results);
        $this->assertEquals('node-1', $results[0]['id']);
        $this->assertEquals('node-2', $results[1]['id']);
    }

    /**
     * 测试解析多个节点时遇到错误抛出异常
     */
    public function test_parse_nodes_throws_exception_on_error()
    {
        $this->expectException(Exception::class);

        $nodes = [
            [
                'id' => 'node-1',
                'type' => 'wait',
                'parameters' => [], // 无效参数
            ],
        ];

        $this->parser->parseNodes($nodes);
    }

    /**
     * 测试解析节点时自动生成 ID
     */
    public function test_parse_node_generates_id_if_missing()
    {
        $node = [
            'type' => 'wait',
            'nodeName' => '等待',
            'parameters' => [
                'mode' => 'after',
                'delay' => 10,
                'unit' => 'minutes',
            ],
        ];

        $result = $this->parser->parseNode($node);

        $this->assertArrayHasKey('id', $result);
        $this->assertNotEmpty($result['id']);
    }

    /**
     * 测试验证所有节点 - 全部有效
     */
    public function test_validate_nodes_returns_valid_for_all_valid_nodes()
    {
        $nodes = [
            [
                'type' => 'wait',
                'parameters' => [
                    'mode' => 'after',
                    'delay' => 10,
                    'unit' => 'minutes',
                ],
            ],
            [
                'type' => 'wait',
                'parameters' => [
                    'mode' => 'at',
                    'time' => '2026-02-26T16:00:00.000Z',
                ],
            ],
        ];

        $result = $this->parser->validateNodes($nodes);

        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['errors']);
    }

    /**
     * 测试验证所有节点 - 存在无效节点
     */
    public function test_validate_nodes_returns_invalid_with_errors()
    {
        $nodes = [
            [
                'type' => 'wait',
                'parameters' => [], // 无效
            ],
            [
                'type' => 'unsupportedType',
                'parameters' => [],
            ],
            [
                // 缺少 type
                'parameters' => [],
            ],
        ];

        $result = $this->parser->validateNodes($nodes);

        $this->assertFalse($result['valid']);
        $this->assertNotEmpty($result['errors']);
        $this->assertCount(3, $result['errors']);
    }

    /**
     * 测试验证空节点数组
     */
    public function test_validate_nodes_returns_valid_for_empty_array()
    {
        $result = $this->parser->validateNodes([]);

        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['errors']);
    }

    /**
     * 测试解析空节点数组
     */
    public function test_parse_nodes_returns_empty_array_for_empty_input()
    {
        $result = $this->parser->parseNodes([]);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /**
     * 测试解析节点时跳过不支持的类型
     */
    public function test_parse_nodes_skips_unsupported_types()
    {
        $nodes = [
            [
                'id' => 'node-1',
                'type' => 'wait',
                'parameters' => [
                    'mode' => 'after',
                    'delay' => 10,
                    'unit' => 'minutes',
                ],
            ],
            [
                'id' => 'node-2',
                'type' => 'unsupportedType',
                'parameters' => [],
            ],
            [
                'id' => 'node-3',
                'type' => 'wait',
                'parameters' => [
                    'mode' => 'after',
                    'delay' => 20,
                    'unit' => 'minutes',
                ],
            ],
        ];

        $results = $this->parser->parseNodes($nodes);

        // 应该只返回 2 个有效节点
        $this->assertCount(2, $results);
        $this->assertEquals('node-1', $results[0]['id']);
        $this->assertEquals('node-3', $results[1]['id']);
    }
}
