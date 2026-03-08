<?php

namespace App\Services\Workflow;

use Carbon\Carbon;
use InvalidArgumentException;

/**
 * 周期型工作流调度时间计算服务
 *
 * 根据前端传入的结构化调度参数（runTime/dayInterval/weekDays/monthDays/executeTime 等），
 * 计算下一次执行时间 next_run_at。
 */
class WorkflowPeriodicScheduler
{
    /**
     * 星期映射：前端值 → ISO 星期编号（周一=1 … 周日=7）
     *
     * 注意：不使用 Carbon::SUNDAY（值为 0），因为 buildWeekCandidates
     * 按 dayOfWeek - 1 计算偏移，SUNDAY=0 会导致偏移为 -1 回退到上一周。
     */
    private const array WEEK_DAY_MAP = [
        'monday' => 1,   // Carbon::MONDAY
        'tuesday' => 2,  // Carbon::TUESDAY
        'wednesday' => 3, // Carbon::WEDNESDAY
        'thursday' => 4, // Carbon::THURSDAY
        'friday' => 5,   // Carbon::FRIDAY
        'saturday' => 6, // Carbon::SATURDAY
        'sunday' => 7,   // ISO-8601 Sunday (不能用 Carbon::SUNDAY=0)
    ];

    /**
     * 根据周期配置和参考时间计算下一次执行时间
     *
     * @param  array<string, mixed>  $config  前端传入的周期调度参数
     * @param  Carbon|null  $from  参考基准时间（默认当前时间）
     * @param  bool  $allowToday  当为 true 时，即使当前时间已过，也返回今天的执行时间点
     */
    public function calculateNextRunAt(array $config, ?Carbon $from = null, bool $allowToday = false): Carbon
    {
        $from = ($from ?? Carbon::now())->copy();
        $runTime = (string) ($config['runTime'] ?? 'day');
        $executeTime = $this->parseExecuteTime($config['executeTime'] ?? '09:00');

        return match ($runTime) {
            'day' => $this->calculateNextDayRun($config, $from, $executeTime, $allowToday),
            'week' => $this->calculateNextWeekRun($config, $from, $executeTime, $allowToday),
            'month' => $this->calculateNextMonthRun($config, $from, $executeTime, $allowToday),
            default => throw new InvalidArgumentException("不支持的周期类型: {$runTime}"),
        };
    }

    /**
     * 从 rule_chain 节点中提取周期调度配置
     *
     * @param  array<string, mixed>  $ruleChain
     * @return array<string, mixed>|null
     */
    public function extractPeriodicConfig(array $ruleChain): ?array
    {
        $nodes = is_array($ruleChain['nodes'] ?? null) ? $ruleChain['nodes'] : [];

        foreach ($nodes as $node) {
            if (($node['type'] ?? null) !== 'start_periodic') {
                continue;
            }

            foreach (['parameters', 'formData', 'props'] as $field) {
                $value = $node[$field] ?? null;
                if (is_array($value)) {
                    return $value;
                }
            }

            return [];
        }

        return null;
    }

    /**
     * 将前端周期配置规范化为可存储的数组（存入 workflows.cron 字段，由 Eloquent json cast 自动序列化）
     *
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    public function serializeConfig(array $config): array
    {
        return [
            'runTime' => $config['runTime'] ?? 'day',
            'dayInterval' => (int) ($config['dayInterval'] ?? 1),
            'weekInterval' => (int) ($config['weekInterval'] ?? 1),
            'weekDays' => $config['weekDays'] ?? [],
            'monthInterval' => (int) ($config['monthInterval'] ?? 1),
            'monthDays' => $config['monthDays'] ?? [],
            'executeTime' => $config['executeTime'] ?? '09:00',
        ];
    }

    /**
     * 从 workflows.cron 字段读取周期配置（Eloquent json cast 已自动反序列化为数组）
     *
     * @return array<string, mixed>|null
     */
    public function deserializeConfig(?array $cron): ?array
    {
        return is_array($cron) ? $cron : null;
    }

    /**
     * 按天计算下一次执行时间
     */
    private function calculateNextDayRun(array $config, Carbon $from, array $time, bool $allowToday = false): Carbon
    {
        $interval = max(1, (int) ($config['dayInterval'] ?? 1));

        // 今天的执行时间
        $candidate = $from->copy()->setTime($time['hour'], $time['minute'], 0);

        // 如果今天的执行时间还没过，或者允许返回今天的时间点
        if ($candidate->gt($from) || $allowToday) {
            return $candidate;
        }

        // 否则跳到下一个周期
        return $candidate->addDays($interval);
    }

    /**
     * 按周计算下一次执行时间
     */
    private function calculateNextWeekRun(array $config, Carbon $from, array $time, bool $allowToday = false): Carbon
    {
        $interval = max(1, (int) ($config['weekInterval'] ?? 1));
        $weekDays = $this->normalizeWeekDays($config['weekDays'] ?? []);

        if (empty($weekDays)) {
            // 无指定星期，退化为按周间隔的周一执行
            $weekDays = [Carbon::MONDAY];
        }

        // 排序，方便查找
        sort($weekDays);

        // 从今天开始，在当前周内寻找匹配的星期
        $currentWeekStart = $from->copy()->startOfWeek(Carbon::MONDAY);
        $candidatesThisWeek = $this->buildWeekCandidates($currentWeekStart, $weekDays, $time);

        foreach ($candidatesThisWeek as $candidate) {
            if ($candidate->gt($from) || $allowToday) {
                return $candidate;
            }
        }

        // 当前周没有合适的时间，跳 N 周
        $nextWeekStart = $currentWeekStart->copy()->addWeeks($interval);
        $candidatesNextWeek = $this->buildWeekCandidates($nextWeekStart, $weekDays, $time);

        return $candidatesNextWeek[0];
    }

    /**
     * 按月计算下一次执行时间
     */
    private function calculateNextMonthRun(array $config, Carbon $from, array $time, bool $allowToday = false): Carbon
    {
        $interval = max(1, (int) ($config['monthInterval'] ?? 1));
        $monthDays = $this->normalizeMonthDays($config['monthDays'] ?? []);

        if (empty($monthDays)) {
            // 无指定日期，退化为每月1号
            $monthDays = [1];
        }

        sort($monthDays);

        // 在当前月寻找
        $candidatesThisMonth = $this->buildMonthCandidates($from->year, $from->month, $monthDays, $time);

        foreach ($candidatesThisMonth as $candidate) {
            if ($candidate->gt($from) || $allowToday) {
                return $candidate;
            }
        }

        // 跳 N 月
        $nextMonth = $from->copy()->startOfMonth()->addMonths($interval);
        $candidatesNextMonth = $this->buildMonthCandidates($nextMonth->year, $nextMonth->month, $monthDays, $time);

        // 如果目标月份没有匹配日期（如2月无31号），继续往后找
        if (empty($candidatesNextMonth)) {
            return $this->findNextMonthWithValidDay($nextMonth, $interval, $monthDays, $time);
        }

        return $candidatesNextMonth[0];
    }

    /**
     * 构建某一周内所有候选执行时间
     *
     * @param  array<int, int>  $weekDays  ISO 星期编号列表（1=周一 … 7=周日）
     * @param  array{hour: int, minute: int}  $time
     * @return array<int, Carbon>
     */
    private function buildWeekCandidates(Carbon $weekStart, array $weekDays, array $time): array
    {
        $candidates = [];

        foreach ($weekDays as $dayOfWeek) {
            // ISO: Monday=1 ... Sunday=7，weekStart 是周一，偏移 = dayOfWeek - 1
            $offset = $dayOfWeek - 1;
            $candidate = $weekStart->copy()->addDays($offset)->setTime($time['hour'], $time['minute'], 0);
            $candidates[] = $candidate;
        }

        usort($candidates, fn (Carbon $a, Carbon $b) => $a->timestamp <=> $b->timestamp);

        return $candidates;
    }

    /**
     * 构建某月内所有候选执行时间
     *
     * @param  array<int, int>  $monthDays
     * @param  array{hour: int, minute: int}  $time
     * @return array<int, Carbon>
     */
    private function buildMonthCandidates(int $year, int $month, array $monthDays, array $time): array
    {
        $daysInMonth = Carbon::createFromDate($year, $month, 1)->daysInMonth;
        $candidates = [];

        foreach ($monthDays as $day) {
            if ($day > $daysInMonth) {
                continue;
            }
            $candidates[] = Carbon::createFromDate($year, $month, $day)->setTime($time['hour'], $time['minute'], 0);
        }

        return $candidates;
    }

    /**
     * 向后查找有效的月份执行日期（处理如31号在短月份不存在的情况）
     */
    private function findNextMonthWithValidDay(Carbon $startMonth, int $interval, array $monthDays, array $time): Carbon
    {
        $maxAttempts = 24;
        $current = $startMonth->copy();

        for ($i = 0; $i < $maxAttempts; $i++) {
            $current = $current->copy()->addMonths($interval);
            $candidates = $this->buildMonthCandidates($current->year, $current->month, $monthDays, $time);
            if (! empty($candidates)) {
                return $candidates[0];
            }
        }

        // 最终兜底：使用该月最后一天
        return $current->copy()->endOfMonth()->setTime($time['hour'], $time['minute'], 0);
    }

    /**
     * 解析 executeTime 字符串为时/分
     *
     * @return array{hour: int, minute: int}
     */
    private function parseExecuteTime(mixed $value): array
    {
        $text = trim((string) ($value ?? ''));

        if (preg_match('/^(\d{1,2}):(\d{2})$/', $text, $matches)) {
            return [
                'hour' => min(23, max(0, (int) $matches[1])),
                'minute' => min(59, max(0, (int) $matches[2])),
            ];
        }

        return ['hour' => 9, 'minute' => 0];
    }

    /**
     * 将前端 weekDays 字符串数组转为 Carbon dayOfWeekIso 值数组
     *
     * @param  array<int, string>  $weekDays
     * @return array<int, int>
     */
    private function normalizeWeekDays(array $weekDays): array
    {
        $result = [];

        foreach ($weekDays as $day) {
            $lower = strtolower(trim((string) $day));
            if (isset(self::WEEK_DAY_MAP[$lower])) {
                $result[] = self::WEEK_DAY_MAP[$lower];
            }
        }

        return array_values(array_unique($result));
    }

    /**
     * 规范化月份日期列表
     *
     * @param  array<int, mixed>  $monthDays
     * @return array<int, int>
     */
    private function normalizeMonthDays(array $monthDays): array
    {
        $result = [];

        foreach ($monthDays as $day) {
            $num = (int) $day;
            if ($num >= 1 && $num <= 31) {
                $result[] = $num;
            }
        }

        return array_values(array_unique($result));
    }
}
