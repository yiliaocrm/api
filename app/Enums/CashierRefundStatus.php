<?php

namespace App\Enums;

enum CashierRefundStatus: int
{
    /**
     * 待审核
     */
    case PENDING_REVIEW = 1;

    /**
     * 待收费
     */
    case PENDING_CHARGE = 2;

    /**
     * 已收费
     */
    case CHARGED = 3;

    /**
     * 退单
     */
    case CANCELLED = 4;

    public function getLabel(): string
    {
        return match ($this) {
            self::PENDING_REVIEW => '待审核',
            self::PENDING_CHARGE => '待收费',
            self::CHARGED => '已收费',
            self::CANCELLED => '退单',
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
