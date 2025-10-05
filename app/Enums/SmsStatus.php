<?php

namespace App\Enums;

enum SmsStatus: string
{
    case PENDING = 'pending';
    case SENT = 'sent';
    case FAILED = 'failed';

    public function getLabel(): string
    {
        return match ($this) {
            self::PENDING => '等待发送',
            self::SENT => '发送成功',
            self::FAILED => '发送失败',
        };
    }

    public static function getOptions(): array
    {
        return collect(self::cases())->mapWithKeys(function ($case) {
            return [$case->value => $case->getLabel()];
        })->toArray();
    }
}
