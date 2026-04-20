<?php

namespace App\Enums;

enum ErkaiStatus: int
{
    /**
     * 未保存
     */
    case UNSAVED = 0;

    /**
     * 待收费
     */
    case PENDING_CHARGE = 1;

    /**
     * 已成交
     */
    case DEAL = 2;

    /**
     * 已取消
     */
    case CANCELLED = 3;

    public function getLabel(): string
    {
        return match ($this) {
            self::UNSAVED => '未保存',
            self::PENDING_CHARGE => '待收费',
            self::DEAL => '已成交',
            self::CANCELLED => '已取消',
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
