<?php

namespace App\Enums;

enum AppointmentStatus: int
{
    case PENDING_CONFIRM = 0;   // 待确认
    case PENDING_ARRIVAL = 1;   // 待上门
    case ARRIVED = 2;   // 已到店
    case RECEIVED = 3;  // 已接待
    case CHARGED = 4;   // 已收费
    case TREATED = 5;   // 已治疗
    case TIMEOUT = 6;   // 已超时
    case LEFT = 7;      // 已离开
    case CANCELLED = 8; // 已取消

    public function getLabel(): string
    {
        return match ($this) {
            self::PENDING_CONFIRM => '待确认',
            self::PENDING_ARRIVAL => '待上门',
            self::ARRIVED => '已到店',
            self::RECEIVED => '已接待',
            self::CHARGED => '已收费',
            self::TREATED => '已治疗',
            self::TIMEOUT => '已超时',
            self::LEFT => '已离开',
            self::CANCELLED => '已取消',
        };
    }

    /**
     * 获取枚举选项
     * @param array $except 排除的枚举项
     * @return array
     */
    public static function options(array $except = []): array
    {
        return collect(self::cases())
            ->filter(fn($case) => !in_array($case, $except))
            ->mapWithKeys(fn($case) => [$case->value => $case->getLabel()])
            ->toArray();
    }
}
