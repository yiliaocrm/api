<?php

namespace App\Services\CustomerLogRemark\Contracts;

interface ValueFormatter
{
    public function format(mixed $value, array $options = []): string;
}
