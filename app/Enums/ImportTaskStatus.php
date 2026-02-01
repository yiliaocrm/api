<?php

namespace App\Enums;

enum ImportTaskStatus: int
{
    /**
     * 未导入
     */
    case PENDING = 0;
    /**
     * 导入中
     */
    case IMPORTING = 1;
    /**
     * 导入完成
     */
    case COMPLETED = 2;
    /**
     * 预检测中
     */
    case PREPARING = 3;

    public function getLabel(): string
    {
        return match ($this) {
            self::PENDING => '待处理',
            self::IMPORTING => '导入中',
            self::COMPLETED => '导入完成',
            self::PREPARING => '预检测中',
        };
    }

    /**
     * 获取枚举选项
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn ($case) => [$case->value => $case->getLabel()])
            ->toArray();
    }
}
