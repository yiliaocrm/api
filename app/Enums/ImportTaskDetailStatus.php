<?php

namespace App\Enums;

enum ImportTaskDetailStatus: int
{
    /**
     * 未导入
     */
    case PENDING = 0;
    /**
     * 成功
     */
    case SUCCESS = 1;
    /**
     * 失败
     */
    case FAILED = 2;

    public function getLabel(): string
    {
        return match ($this) {
            self::PENDING => '未导入',
            self::SUCCESS => '成功',
            self::FAILED => '失败',
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
