<?php

namespace App\Enums;

enum AppointmentType: string
{
    /**
     * 面诊预约
     */
    case COMING = 'coming';
    /**
     * 治疗预约
     */
    case TREATMENT = 'treatment';
    /**
     * 手术预约
     */
    case OPERATION = 'operation';

    public function getLabel(): string
    {
        return match ($this) {
            self::COMING => '面诊预约',
            self::TREATMENT => '治疗预约',
            self::OPERATION => '手术预约',
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
