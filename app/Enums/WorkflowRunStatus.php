<?php

namespace App\Enums;

enum WorkflowRunStatus: string
{
    case PENDING = 'pending';
    case RUNNING = 'running';
    case COMPLETED = 'completed';
    case CANCELED = 'canceled';
    case ERROR = 'error';

    public function getLabel(): string
    {
        return match ($this) {
            self::PENDING => '待处理',
            self::RUNNING => '运行中',
            self::COMPLETED => '已完成',
            self::CANCELED => '已取消',
            self::ERROR => '错误',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn ($case) => [$case->value => $case->getLabel()])
            ->toArray();
    }
}
