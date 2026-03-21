<?php

namespace App\Services\CustomerLogRemark\Profiles;

use App\Services\CustomerLogRemark\Contracts\RemarkProfile;
use App\Services\CustomerLogRemark\Formatters\RawValueFormatter;

class DefaultRemarkProfile implements RemarkProfile
{
    public function labelFor(string $field): string
    {
        return "字段 {$field}";
    }

    public function formatterFor(string $field): string
    {
        return RawValueFormatter::class;
    }

    public function formatterOptionsFor(string $field): array
    {
        return [];
    }

    public function ignores(string $field): bool
    {
        return in_array($field, ['updated_at', 'deleted_at'], true);
    }
}
