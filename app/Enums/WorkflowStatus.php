<?php

namespace App\Enums;

enum WorkflowStatus: string
{
    case DRAFT = 'draft';
    case PENDING = 'pending';
    case ACTIVE = 'active';
    case PAUSED = 'paused';
    case COMPLETED = 'completed';

    public function getLabel(): string
    {
        return match ($this) {
            self::DRAFT => '草稿',
            self::PENDING => '未开始',
            self::ACTIVE => '进行中',
            self::PAUSED => '已暂停',
            self::COMPLETED => '已结束',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn ($case) => [$case->value => $case->getLabel()])
            ->toArray();
    }
}
