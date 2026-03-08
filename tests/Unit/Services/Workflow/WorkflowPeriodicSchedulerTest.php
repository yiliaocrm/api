<?php

namespace Tests\Unit\Services\Workflow;

use App\Services\Workflow\WorkflowPeriodicScheduler;
use Carbon\Carbon;
use InvalidArgumentException;
use Tests\TestCase;

class WorkflowPeriodicSchedulerTest extends TestCase
{
    private WorkflowPeriodicScheduler $scheduler;

    protected function setUp(): void
    {
        parent::setUp();
        $this->scheduler = new WorkflowPeriodicScheduler;
    }

    // ───────────── 按天（day）模式 ─────────────

    public function test_day_interval_1_returns_today_if_time_not_passed(): void
    {
        // 当前 08:00，执行时间 09:00 → 今天 09:00
        $from = Carbon::create(2026, 2, 25, 8, 0, 0);

        $result = $this->scheduler->calculateNextRunAt([
            'runTime' => 'day',
            'dayInterval' => 1,
            'executeTime' => '09:00',
        ], $from);

        $this->assertEquals(Carbon::create(2026, 2, 25, 9, 0, 0), $result);
    }

    public function test_day_interval_1_returns_tomorrow_if_time_passed(): void
    {
        // 当前 10:00，执行时间 09:00 → 明天 09:00
        $from = Carbon::create(2026, 2, 25, 10, 0, 0);

        $result = $this->scheduler->calculateNextRunAt([
            'runTime' => 'day',
            'dayInterval' => 1,
            'executeTime' => '09:00',
        ], $from);

        $this->assertEquals(Carbon::create(2026, 2, 26, 9, 0, 0), $result);
    }

    public function test_day_interval_3_skips_correct_days(): void
    {
        // 当前 10:00，执行时间 09:00，间隔3天 → 3天后 09:00
        $from = Carbon::create(2026, 2, 25, 10, 0, 0);

        $result = $this->scheduler->calculateNextRunAt([
            'runTime' => 'day',
            'dayInterval' => 3,
            'executeTime' => '09:00',
        ], $from);

        $this->assertEquals(Carbon::create(2026, 2, 28, 9, 0, 0), $result);
    }

    public function test_day_with_custom_execute_time(): void
    {
        $from = Carbon::create(2026, 3, 1, 8, 0, 0);

        $result = $this->scheduler->calculateNextRunAt([
            'runTime' => 'day',
            'dayInterval' => 1,
            'executeTime' => '14:30',
        ], $from);

        $this->assertEquals(Carbon::create(2026, 3, 1, 14, 30, 0), $result);
    }

    // ───────────── 按周（week）模式 ─────────────

    public function test_week_returns_this_week_if_matching_day_has_not_passed(): void
    {
        // 2026-02-25 是周三 08:00，选周三和周五
        $from = Carbon::create(2026, 2, 25, 8, 0, 0);

        $result = $this->scheduler->calculateNextRunAt([
            'runTime' => 'week',
            'weekInterval' => 1,
            'weekDays' => ['wednesday', 'friday'],
            'executeTime' => '10:00',
        ], $from);

        $this->assertEquals(Carbon::create(2026, 2, 25, 10, 0, 0), $result);
    }

    public function test_week_returns_next_matching_day_within_same_week(): void
    {
        // 2026-02-25 是周三 12:00（已过10:00），选周三和周五 → 周五10:00
        $from = Carbon::create(2026, 2, 25, 12, 0, 0);

        $result = $this->scheduler->calculateNextRunAt([
            'runTime' => 'week',
            'weekInterval' => 1,
            'weekDays' => ['wednesday', 'friday'],
            'executeTime' => '10:00',
        ], $from);

        $this->assertEquals(Carbon::create(2026, 2, 27, 10, 0, 0), $result);
    }

    public function test_week_skips_to_next_interval_when_all_days_passed(): void
    {
        // 2026-02-27 是周五 12:00（已过10:00），选周三和周五，间隔2周
        // 当前周的所有 weekDays 都已过 → 跳2周到下一个周三
        $from = Carbon::create(2026, 2, 27, 12, 0, 0);

        $result = $this->scheduler->calculateNextRunAt([
            'runTime' => 'week',
            'weekInterval' => 2,
            'weekDays' => ['wednesday', 'friday'],
            'executeTime' => '10:00',
        ], $from);

        // 2026-02-23（周一）是当前周开始 + 2周 = 2026-03-09（周一），周三=2026-03-11
        $this->assertEquals(Carbon::create(2026, 3, 11, 10, 0, 0), $result);
    }

    public function test_week_with_single_day(): void
    {
        // 2026-02-25 周三 08:00，只选周一，间隔1周
        $from = Carbon::create(2026, 2, 25, 8, 0, 0);

        $result = $this->scheduler->calculateNextRunAt([
            'runTime' => 'week',
            'weekInterval' => 1,
            'weekDays' => ['monday'],
            'executeTime' => '09:00',
        ], $from);

        // 当前周周一已过（2/23），下周周一 = 3/2
        $this->assertEquals(Carbon::create(2026, 3, 2, 9, 0, 0), $result);
    }

    public function test_week_empty_weekdays_defaults_to_monday(): void
    {
        $from = Carbon::create(2026, 2, 25, 8, 0, 0);

        $result = $this->scheduler->calculateNextRunAt([
            'runTime' => 'week',
            'weekInterval' => 1,
            'weekDays' => [],
            'executeTime' => '09:00',
        ], $from);

        // 周一已过，下周周一
        $this->assertEquals(Carbon::create(2026, 3, 2, 9, 0, 0), $result);
    }

    public function test_week_sunday_only_returns_this_week_sunday(): void
    {
        // 2026-02-25 是周三 08:00，只选周日
        // 本周周日 = 2026-03-01
        $from = Carbon::create(2026, 2, 25, 8, 0, 0);

        $result = $this->scheduler->calculateNextRunAt([
            'runTime' => 'week',
            'weekInterval' => 1,
            'weekDays' => ['sunday'],
            'executeTime' => '10:00',
        ], $from);

        $this->assertEquals(Carbon::create(2026, 3, 1, 10, 0, 0), $result);
        // 确认确实是周日
        $this->assertSame(0, $result->dayOfWeek, '结果应当是周日(dayOfWeek=0)');
    }

    public function test_week_sunday_mixed_with_other_days(): void
    {
        // 2026-02-28 是周六 12:00，选周五和周日
        // 周五(2/27)已过，周日(3/1)还没到 → 应返回本周日
        $from = Carbon::create(2026, 2, 28, 12, 0, 0);

        $result = $this->scheduler->calculateNextRunAt([
            'runTime' => 'week',
            'weekInterval' => 1,
            'weekDays' => ['friday', 'sunday'],
            'executeTime' => '10:00',
        ], $from);

        $this->assertEquals(Carbon::create(2026, 3, 1, 10, 0, 0), $result);
    }

    public function test_week_sunday_already_passed_skips_to_next_interval(): void
    {
        // 2026-03-01 是周日 12:00（已过10:00），只选周日，间隔1周
        // 本周周日已过 → 下周周日 = 3/8
        $from = Carbon::create(2026, 3, 1, 12, 0, 0);

        $result = $this->scheduler->calculateNextRunAt([
            'runTime' => 'week',
            'weekInterval' => 1,
            'weekDays' => ['sunday'],
            'executeTime' => '10:00',
        ], $from);

        $this->assertEquals(Carbon::create(2026, 3, 8, 10, 0, 0), $result);
    }

    // ───────────── 按月（month）模式 ─────────────

    public function test_month_returns_this_month_if_matching_day_not_passed(): void
    {
        // 2026-02-10 08:00，选15号和28号
        $from = Carbon::create(2026, 2, 10, 8, 0, 0);

        $result = $this->scheduler->calculateNextRunAt([
            'runTime' => 'month',
            'monthInterval' => 1,
            'monthDays' => [15, 28],
            'executeTime' => '14:00',
        ], $from);

        $this->assertEquals(Carbon::create(2026, 2, 15, 14, 0, 0), $result);
    }

    public function test_month_returns_next_day_within_same_month(): void
    {
        // 2026-02-15 16:00（已过14:00），选15号和28号
        $from = Carbon::create(2026, 2, 15, 16, 0, 0);

        $result = $this->scheduler->calculateNextRunAt([
            'runTime' => 'month',
            'monthInterval' => 1,
            'monthDays' => [15, 28],
            'executeTime' => '14:00',
        ], $from);

        $this->assertEquals(Carbon::create(2026, 2, 28, 14, 0, 0), $result);
    }

    public function test_month_skips_to_next_interval(): void
    {
        // 2026-02-28 16:00，所有当月日期已过，间隔2月
        $from = Carbon::create(2026, 2, 28, 16, 0, 0);

        $result = $this->scheduler->calculateNextRunAt([
            'runTime' => 'month',
            'monthInterval' => 2,
            'monthDays' => [15],
            'executeTime' => '09:00',
        ], $from);

        $this->assertEquals(Carbon::create(2026, 4, 15, 9, 0, 0), $result);
    }

    public function test_month_skips_31st_in_february(): void
    {
        // 2026-01-31 16:00，选31号，间隔1月
        // 2月没有31号，应该跳到下一个有31号的月份
        $from = Carbon::create(2026, 1, 31, 16, 0, 0);

        $result = $this->scheduler->calculateNextRunAt([
            'runTime' => 'month',
            'monthInterval' => 1,
            'monthDays' => [31],
            'executeTime' => '09:00',
        ], $from);

        // 2月无31号，跳到3月31号
        $this->assertEquals(Carbon::create(2026, 3, 31, 9, 0, 0), $result);
    }

    public function test_month_empty_monthdays_defaults_to_first(): void
    {
        $from = Carbon::create(2026, 2, 10, 8, 0, 0);

        $result = $this->scheduler->calculateNextRunAt([
            'runTime' => 'month',
            'monthInterval' => 1,
            'monthDays' => [],
            'executeTime' => '09:00',
        ], $from);

        // 当月1号已过，下月1号
        $this->assertEquals(Carbon::create(2026, 3, 1, 9, 0, 0), $result);
    }

    // ───────────── 边界：时间精度 ─────────────

    /**
     * 基准时间恰好等于 executeTime 时，应跳到下一个周期（不重复触发今天）
     *
     * 这是命令启动时刻的时序边界：next_run_at 恰为 22:05:00，
     * 命令在 22:05:00 整秒触发，$from = 22:05:00。
     * gt(22:05:00, 22:05:00) = false → 正确返回明天 22:05:00。
     */
    public function test_day_from_exactly_equal_to_execute_time_returns_next_day(): void
    {
        $from = Carbon::create(2026, 2, 25, 22, 5, 0);

        $result = $this->scheduler->calculateNextRunAt([
            'runTime' => 'day',
            'dayInterval' => 1,
            'executeTime' => '22:05',
        ], $from);

        $this->assertEquals(Carbon::create(2026, 2, 26, 22, 5, 0), $result);
    }

    /**
     * 基准时间比 executeTime 晚 1 秒时，应返回下一周期（正常情况）
     *
     * 对应命令修复后的行为：calculateNextRunAt 改用 Carbon::now()（实时获取），
     * 此时已完成 chunk 处理，时间一定晚于 executeTime，应返回明天。
     */
    public function test_day_from_one_second_after_execute_time_returns_next_day(): void
    {
        $from = Carbon::create(2026, 2, 25, 22, 5, 1);

        $result = $this->scheduler->calculateNextRunAt([
            'runTime' => 'day',
            'dayInterval' => 1,
            'executeTime' => '22:05',
        ], $from);

        $this->assertEquals(Carbon::create(2026, 2, 26, 22, 5, 0), $result);
    }

    /**
     * 基准时间比 executeTime 早 1 秒时，应返回今天（调度前查询到的合法窗口）
     *
     * 此测试记录调度器在被传入「早于 executeTime 的基准时间」时的行为。
     * 命令层已通过 Carbon::now()（实时重新获取）规避此问题，
     * 但调度器本身行为仍保持不变：当 $from < executeTime，返回今天的执行时间。
     */
    public function test_day_from_one_second_before_execute_time_still_returns_today(): void
    {
        $from = Carbon::create(2026, 2, 25, 22, 4, 59);

        $result = $this->scheduler->calculateNextRunAt([
            'runTime' => 'day',
            'dayInterval' => 1,
            'executeTime' => '22:05',
        ], $from);

        $this->assertEquals(Carbon::create(2026, 2, 25, 22, 5, 0), $result);
    }

    // ───────────── 异常 & 边界 ─────────────

    public function test_invalid_run_time_throws_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('不支持的周期类型');

        $this->scheduler->calculateNextRunAt([
            'runTime' => 'invalid',
        ]);
    }

    public function test_default_execute_time_fallback(): void
    {
        $from = Carbon::create(2026, 2, 25, 8, 0, 0);

        $result = $this->scheduler->calculateNextRunAt([
            'runTime' => 'day',
            'dayInterval' => 1,
            'executeTime' => 'invalid',
        ], $from);

        // 默认 09:00，当天还没到 → 今天 09:00
        $this->assertEquals(Carbon::create(2026, 2, 25, 9, 0, 0), $result);
    }

    // ───────────── extractPeriodicConfig ─────────────

    public function test_extract_periodic_config_from_rule_chain(): void
    {
        $ruleChain = [
            'nodes' => [
                [
                    'id' => 'start',
                    'type' => 'start_periodic',
                    'parameters' => [
                        'journeyType' => 'periodic',
                        'runTime' => 'week',
                        'weekInterval' => 2,
                        'weekDays' => ['monday', 'friday'],
                        'executeTime' => '10:00',
                    ],
                ],
                ['id' => 'end', 'type' => 'end'],
            ],
        ];

        $config = $this->scheduler->extractPeriodicConfig($ruleChain);

        $this->assertIsArray($config);
        $this->assertSame('week', $config['runTime']);
        $this->assertSame(2, $config['weekInterval']);
        $this->assertSame(['monday', 'friday'], $config['weekDays']);
    }

    public function test_extract_periodic_config_returns_null_when_no_start_periodic(): void
    {
        $ruleChain = [
            'nodes' => [
                ['id' => 'start', 'type' => 'start_trigger'],
                ['id' => 'end', 'type' => 'end'],
            ],
        ];

        $this->assertNull($this->scheduler->extractPeriodicConfig($ruleChain));
    }

    // ───────────── serialize / deserialize ─────────────

    public function test_serialize_and_deserialize_config(): void
    {
        $config = [
            'runTime' => 'week',
            'dayInterval' => 1,
            'weekInterval' => 2,
            'weekDays' => ['monday', 'friday'],
            'monthInterval' => 1,
            'monthDays' => [],
            'executeTime' => '10:00',
        ];

        // serializeConfig 现在返回 array，不再是 string
        $serialized = $this->scheduler->serializeConfig($config);
        $this->assertIsArray($serialized);
        $this->assertSame('week', $serialized['runTime']);

        // deserializeConfig 接受 array|null
        $deserialized = $this->scheduler->deserializeConfig($serialized);
        $this->assertSame('week', $deserialized['runTime']);
        $this->assertSame(2, $deserialized['weekInterval']);
        $this->assertSame(['monday', 'friday'], $deserialized['weekDays']);
    }

    public function test_deserialize_null_returns_null(): void
    {
        $this->assertNull($this->scheduler->deserializeConfig(null));
    }

    public function test_deserialize_array_returns_array(): void
    {
        $config = ['runTime' => 'day', 'executeTime' => '09:00'];
        $this->assertSame($config, $this->scheduler->deserializeConfig($config));
    }
}
