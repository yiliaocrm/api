<?php

namespace App\Enums;

enum FollowupStatus: int
{
    /**
     * 未回访
     */
    case PENDING = 1;
    /**
     * 已回访
     */
    case COMPLETED = 2;

    public function getLabel(): string
    {
        return match ($this) {
            self::PENDING => '未回访',
            self::COMPLETED => '已回访',
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
