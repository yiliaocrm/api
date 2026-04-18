<?php

namespace App\Enums;

enum CashierRetailStatus: int
{
    /**
     * 挂单
     */
    case PENDING = 1;

    /**
     * 成交
     */
    case DEAL = 2;

    public function getLabel(): string
    {
        return match ($this) {
            self::PENDING => '挂单',
            self::DEAL => '成交',
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
