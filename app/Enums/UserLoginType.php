<?php

namespace App\Enums;

enum UserLoginType: int
{
    case PC = 1;    // PC端
    case APP = 2;   // APP端

    public function getLabel(): string
    {
        return match ($this) {
            self::PC => 'PC端',
            self::APP => 'APP端',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(function ($case) {
            return [$case->value => $case->getLabel()];
        })->toArray();
    }
}
