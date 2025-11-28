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

    public function getLabel(): string
    {
        return match ($this) {
            self::PENDING => '未导入',
            self::IMPORTING => '导入中',
            self::COMPLETED => '导入完成',
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
