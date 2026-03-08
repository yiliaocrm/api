<?php

namespace App\Services\Workflow\Parsers;

use App\Services\Workflow\Contracts\NodeParserInterface;
use Carbon\Carbon;

class WaitNodeParser implements NodeParserInterface
{
    /**
     * 解析等待节点为 RuleGo delay 组件格式
     *
     * @param  array  $node  前端节点数据
     * @return array RuleGo delay 组件配置
     */
    public function parse(array $node): array
    {
        $parameters = $node['parameters'] ?? [];
        $mode = $parameters['mode'] ?? 'after';

        // 构建 RuleGo delay 组件的配置
        $ruleGoConfig = [
            'type' => 'x/delay',
            'name' => $node['nodeName'] ?? '等待',
            'debugMode' => false,
            'configuration' => $this->buildConfiguration($parameters, $mode),
        ];

        return $ruleGoConfig;
    }

    /**
     * 构建 RuleGo delay 组件的 configuration
     *
     * @param  array  $parameters  前端参数
     * @param  string  $mode  等待模式
     */
    protected function buildConfiguration(array $parameters, string $mode): array
    {
        $config = [];

        if ($mode === 'at') {
            // 指定时间模式
            $config['periodInSeconds'] = $this->calculatePeriodInSeconds($parameters['time'] ?? null);
        } else {
            // 相对时间模式
            $delay = $parameters['delay'] ?? 1;
            $unit = $parameters['unit'] ?? 'minutes';
            $config['periodInSeconds'] = $this->convertToSeconds($delay, $unit);
        }

        // 是否覆盖待处理消息
        if (isset($parameters['overwrite'])) {
            $config['overwrite'] = (bool) $parameters['overwrite'];
        }

        return $config;
    }

    /**
     * 计算指定时间到当前时间的秒数
     *
     * @param  string|null  $time  YYYY-MM-DD HH:mm:ss 格式的时间字符串
     */
    protected function calculatePeriodInSeconds(?string $time): int
    {
        if (empty($time)) {
            return 0;
        }

        $targetTime = Carbon::parse($time);
        $now = Carbon::now();

        // 如果目标时间已过，返回 0
        if ($targetTime->lte($now)) {
            return 0;
        }

        return $targetTime->diffInSeconds($now);
    }

    /**
     * 将时间单位转换为秒数
     *
     * @param  int  $value  时间数值
     * @param  string  $unit  时间单位 (seconds/minutes/hours/days)
     */
    protected function convertToSeconds(int $value, string $unit): int
    {
        return match ($unit) {
            'seconds' => $value,
            'minutes' => $value * 60,
            'hours' => $value * 3600,
            'days' => $value * 86400,
            default => $value * 60, // 默认为分钟
        };
    }

    /**
     * 验证节点参数是否有效
     *
     * @param  array  $node  前端节点数据
     */
    public function validate(array $node): bool
    {
        $parameters = $node['parameters'] ?? [];
        $mode = $parameters['mode'] ?? null;

        if (! in_array($mode, ['at', 'after'])) {
            return false;
        }

        if ($mode === 'at') {
            // 指定时间模式需要有 time 参数
            return ! empty($parameters['time']);
        }

        // 相对时间模式需要有 delay 和 unit 参数
        return isset($parameters['delay']) && isset($parameters['unit']);
    }

    /**
     * 获取该解析器支持的节点类型
     */
    public function getNodeType(): string
    {
        return 'wait';
    }
}
