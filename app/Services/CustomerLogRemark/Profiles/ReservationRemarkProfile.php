<?php

namespace App\Services\CustomerLogRemark\Profiles;

use App\Services\CustomerLogRemark\Formatters\EnumValueFormatter;

class ReservationRemarkProfile extends DefaultRemarkProfile
{
    public function labelFor(string $field): string
    {
        return match ($field) {
            'status' => '预约状态',
            default => parent::labelFor($field),
        };
    }

    public function formatterFor(string $field): string
    {
        return match ($field) {
            'status' => EnumValueFormatter::class,
            default => parent::formatterFor($field),
        };
    }

    public function formatterOptionsFor(string $field): array
    {
        return match ($field) {
            'status' => ['map' => 'reservation.status'],
            default => parent::formatterOptionsFor($field),
        };
    }
}
