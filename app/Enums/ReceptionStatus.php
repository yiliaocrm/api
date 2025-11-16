<?php

namespace App\Enums;

enum ReceptionStatus: int
{
    /**
     * 未成交
     */
    case FAILED = 1;
    /**
     * 成交
     */
    case COMPLETED = 2;

    public function getLabel(): string
    {
        return match ($this) {
            self::FAILED => '未成交',
            self::COMPLETED => '成交',
        };
    }

    /**
     * 获取枚举选项
     * @return array
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn($case) => [$case->value => $case->getLabel()])
            ->toArray();
    }
}
