<?php

namespace App\Enums;

enum ReceptionStatus: int
{
    case FAILED = 1;        // 未成交
    case COMPLETED = 2;     // 成交

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
