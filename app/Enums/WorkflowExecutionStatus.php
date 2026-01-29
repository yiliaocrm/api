<?php

namespace App\Enums;

enum WorkflowExecutionStatus: string
{
    case RUNNING = 'running';
    case SUCCESS = 'success';
    case ERROR = 'error';
    case WAITING = 'waiting';
    case CANCELED = 'canceled';

    public function getLabel(): string
    {
        return match ($this) {
            self::RUNNING => '运行中',
            self::SUCCESS => '成功',
            self::ERROR => '失败',
            self::WAITING => '等待中',
            self::CANCELED => '已取消',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn ($case) => [$case->value => $case->getLabel()])
            ->toArray();
    }
}
