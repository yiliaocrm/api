<?php

namespace App\Enums;

enum WorkflowType: string
{
    case TRIGGER = 'trigger';
    case PERIODIC = 'periodic';

    public function getLabel(): string
    {
        return match ($this) {
            self::TRIGGER => '触发型',
            self::PERIODIC => '周期型',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn ($case) => [$case->value => $case->getLabel()])
            ->toArray();
    }
}
