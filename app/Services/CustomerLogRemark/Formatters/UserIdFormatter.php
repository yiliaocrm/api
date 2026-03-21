<?php

namespace App\Services\CustomerLogRemark\Formatters;

use App\Services\CustomerLogRemark\Contracts\ValueFormatter;

class UserIdFormatter implements ValueFormatter
{
    public function format(mixed $value, array $options = []): string
    {
        $name = get_user_name((int) $value);

        return $name ?? '';
    }
}
