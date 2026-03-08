<?php

namespace Tests\Unit\Services\Workflow;

use App\Services\Workflow\Parsers\WaitNodeParser;
use Tests\TestCase;

class WaitNodeParserTest extends TestCase
{
    protected WaitNodeParser $parser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new WaitNodeParser;
    }

    /**
     * 测试解析 "指定时间" 模式的等待节点
     */
    public function test_parse_wait_node_with_at_mode()
    {
        $node = [
            'id' => '019c33e0-aed4-756d-a392-c8c0d878b9ca',
            'type' => 'wait',
            'nodeName' => '等待到指定时间',
            'parameters' => [
                'mode' => 'at',
                'time' => '2026-02-26T16:00:00.000Z',
                'overwrite' => false,
            ],
        ];

        $result = $this->parser->parse($node);

        $this->assertEquals('x/delay', $result['type']);
        $this->assertEquals('等待到指定时间', $result['name']);
        $this->assertFalse($result['debugMode']);
        $this->assertArrayHasKey('configuration', $result);
        $this->assertArrayHasKey('periodInSeconds', $result['configuration']);
        $this->assertFalse($result['configuration']['overwrite']);
    }

    /**
     * 测试解析 "相对时间" 模式的等待节点 - 分钟
     */
    public function test_parse_wait_node_with_after_mode_minutes()
    {
        $node = [
            'id' => 'test-node-1',
            'type' => 'wait',
            'nodeName' => '等待15分钟',
            'parameters' => [
                'mode' => 'after',
                'delay' => 15,
                'unit' => 'minutes',
                'overwrite' => true,
            ],
        ];

        $result = $this->parser->parse($node);

        $this->assertEquals('x/delay', $result['type']);
        $this->assertEquals('等待15分钟', $result['name']);
        $this->assertEquals(15 * 60, $result['configuration']['periodInSeconds']);
        $this->assertTrue($result['configuration']['overwrite']);
    }

    /**
     * 测试解析 "相对时间" 模式的等待节点 - 小时
     */
    public function test_parse_wait_node_with_after_mode_hours()
    {
        $node = [
            'type' => 'wait',
            'nodeName' => '等待2小时',
            'parameters' => [
                'mode' => 'after',
                'delay' => 2,
                'unit' => 'hours',
            ],
        ];

        $result = $this->parser->parse($node);

        $this->assertEquals(2 * 3600, $result['configuration']['periodInSeconds']);
    }

    /**
     * 测试解析 "相对时间" 模式的等待节点 - 天
     */
    public function test_parse_wait_node_with_after_mode_days()
    {
        $node = [
            'type' => 'wait',
            'nodeName' => '等待3天',
            'parameters' => [
                'mode' => 'after',
                'delay' => 3,
                'unit' => 'days',
            ],
        ];

        $result = $this->parser->parse($node);

        $this->assertEquals(3 * 86400, $result['configuration']['periodInSeconds']);
    }

    /**
     * 测试解析 "相对时间" 模式的等待节点 - 秒
     */
    public function test_parse_wait_node_with_after_mode_seconds()
    {
        $node = [
            'type' => 'wait',
            'nodeName' => '等待30秒',
            'parameters' => [
                'mode' => 'after',
                'delay' => 30,
                'unit' => 'seconds',
            ],
        ];

        $result = $this->parser->parse($node);

        $this->assertEquals(30, $result['configuration']['periodInSeconds']);
    }

    /**
     * 测试验证有效的 "指定时间" 模式节点
     */
    public function test_validate_returns_true_for_valid_at_mode_node()
    {
        $node = [
            'type' => 'wait',
            'parameters' => [
                'mode' => 'at',
                'time' => '2026-02-26T16:00:00.000Z',
            ],
        ];

        $this->assertTrue($this->parser->validate($node));
    }

    /**
     * 测试验证有效的 "相对时间" 模式节点
     */
    public function test_validate_returns_true_for_valid_after_mode_node()
    {
        $node = [
            'type' => 'wait',
            'parameters' => [
                'mode' => 'after',
                'delay' => 10,
                'unit' => 'minutes',
            ],
        ];

        $this->assertTrue($this->parser->validate($node));
    }

    /**
     * 测试验证缺少 mode 参数的节点
     */
    public function test_validate_returns_false_for_missing_mode()
    {
        $node = [
            'type' => 'wait',
            'parameters' => [],
        ];

        $this->assertFalse($this->parser->validate($node));
    }

    /**
     * 测试验证无效的 mode 值
     */
    public function test_validate_returns_false_for_invalid_mode()
    {
        $node = [
            'type' => 'wait',
            'parameters' => [
                'mode' => 'invalid_mode',
            ],
        ];

        $this->assertFalse($this->parser->validate($node));
    }

    /**
     * 测试验证 "指定时间" 模式缺少 time 参数
     */
    public function test_validate_returns_false_for_at_mode_without_time()
    {
        $node = [
            'type' => 'wait',
            'parameters' => [
                'mode' => 'at',
            ],
        ];

        $this->assertFalse($this->parser->validate($node));
    }

    /**
     * 测试验证 "相对时间" 模式缺少 delay 参数
     */
    public function test_validate_returns_false_for_after_mode_without_delay()
    {
        $node = [
            'type' => 'wait',
            'parameters' => [
                'mode' => 'after',
                'unit' => 'minutes',
            ],
        ];

        $this->assertFalse($this->parser->validate($node));
    }

    /**
     * 测试验证 "相对时间" 模式缺少 unit 参数
     */
    public function test_validate_returns_false_for_after_mode_without_unit()
    {
        $node = [
            'type' => 'wait',
            'parameters' => [
                'mode' => 'after',
                'delay' => 10,
            ],
        ];

        $this->assertFalse($this->parser->validate($node));
    }

    /**
     * 测试获取节点类型
     */
    public function test_get_node_type_returns_wait()
    {
        $this->assertEquals('wait', $this->parser->getNodeType());
    }

    /**
     * 测试默认时间单位（当单位无效时）
     */
    public function test_parse_uses_default_unit_for_invalid_unit()
    {
        $node = [
            'type' => 'wait',
            'nodeName' => '等待',
            'parameters' => [
                'mode' => 'after',
                'delay' => 10,
                'unit' => 'invalid_unit',
            ],
        ];

        $result = $this->parser->parse($node);

        // 默认应该使用分钟
        $this->assertEquals(10 * 60, $result['configuration']['periodInSeconds']);
    }

    /**
     * 测试 overwrite 参数默认不存在
     */
    public function test_parse_without_overwrite_parameter()
    {
        $node = [
            'type' => 'wait',
            'nodeName' => '等待',
            'parameters' => [
                'mode' => 'after',
                'delay' => 5,
                'unit' => 'minutes',
            ],
        ];

        $result = $this->parser->parse($node);

        $this->assertArrayNotHasKey('overwrite', $result['configuration']);
    }
}
