<?php

namespace App\Enums;

enum TreatmentStatus: int
{
    /**
     * 正常
     */
    case NORMAL = 1;
    /**
     * 撤销
     */
    case CANCELLED = 2;

    public function getLabel(): string
    {
        return match ($this) {
            self::NORMAL => '正常',
            self::CANCELLED => '撤销',
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
