<?php

namespace App\Enums;

enum CouponDetailStatus: int
{
    /**
     * 未使用
     */
    case UNUSED = 1;

    /**
     * 部分使用
     */
    case PARTIALLY_USED = 2;

    /**
     * 已使用
     */
    case USED = 3;

    /**
     * 已过期
     */
    case EXPIRED = 4;

    /**
     * 已作废
     */
    case VOIDED = 5;

    public function getLabel(): string
    {
        return match ($this) {
            self::UNUSED => '未使用',
            self::PARTIALLY_USED => '部分使用',
            self::USED => '已使用',
            self::EXPIRED => '已过期',
            self::VOIDED => '已作废',
        };
    }

    public static function options(array $except = []): array
    {
        return collect(self::cases())
            ->filter(fn ($case) => ! in_array($case, $except, true))
            ->mapWithKeys(fn ($case) => [$case->value => $case->getLabel()])
            ->toArray();
    }
}
