<?php

namespace App\Enums;

enum WorkflowStatus: string
{
    case ACTIVE = 'active';
    case PAUSED = 'paused';

    public function getLabel(): string
    {
        return match ($this) {
            self::ACTIVE => '已发布',
            self::PAUSED => '未发布',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn ($case) => [$case->value => $case->getLabel()])
            ->toArray();
    }
}
