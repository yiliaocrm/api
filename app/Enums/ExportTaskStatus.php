<?php

namespace App\Enums;

enum ExportTaskStatus: string
{
    case PENDING = 'pending';       // 待处理
    case PROCESSING = 'processing'; // 处理中
    case COMPLETED = 'completed';   // 完成
    case FAILED = 'failed';         // 失败
    case EXPIRED = 'expired';       // 文件过期

    public function getLabel(): string
    {
        return match ($this) {
            self::PENDING => '待处理',
            self::PROCESSING => '处理中',
            self::COMPLETED => '完成',
            self::FAILED => '失败',
            self::EXPIRED => '文件过期',
        };
    }

    /**
     * 获取枚举选项
     * @param array $except 排除的枚举项
     * @return array
     */
    public static function options(array $except = []): array
    {
        return collect(self::cases())
            ->filter(fn($case) => !in_array($case, $except))
            ->mapWithKeys(fn($case) => [$case->value => $case->getLabel()])
            ->toArray();
    }
}
