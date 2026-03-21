<?php

namespace App\Services\CustomerLogRemark\Profiles;

use App\Services\CustomerLogRemark\Formatters\EnumValueFormatter;
use App\Services\CustomerLogRemark\Formatters\UserIdFormatter;

class CustomerRemarkProfile extends DefaultRemarkProfile
{
    public function labelFor(string $field): string
    {
        return match ($field) {
            'name' => '顾客姓名',
            'consultant' => '销售顾问',
            'sex' => '性别',
            default => parent::labelFor($field),
        };
    }

    public function formatterFor(string $field): string
    {
        return match ($field) {
            'consultant' => UserIdFormatter::class,
            'sex' => EnumValueFormatter::class,
            default => parent::formatterFor($field),
        };
    }

    public function formatterOptionsFor(string $field): array
    {
        return match ($field) {
            'sex' => ['map' => 'customer.sex'],
            default => parent::formatterOptionsFor($field),
        };
    }
}
