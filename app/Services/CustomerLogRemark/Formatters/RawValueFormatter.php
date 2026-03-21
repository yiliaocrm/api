<?php

namespace App\Services\CustomerLogRemark\Formatters;

use App\Services\CustomerLogRemark\Contracts\ValueFormatter;

class RawValueFormatter implements ValueFormatter
{
    public function format(mixed $value, array $options = []): string
    {
        if (is_array($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '';
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if ($value === null) {
            return '';
        }

        return (string) $value;
    }
}
