<?php

namespace App\Services\CustomerLogRemark\Formatters;

use App\Services\CustomerLogRemark\Contracts\ValueFormatter;
use App\Services\CustomerLogRemark\Support\LogRemarkEnumRegistry;

class EnumValueFormatter implements ValueFormatter
{
    public function __construct(
        private readonly LogRemarkEnumRegistry $registry
    ) {}

    public function format(mixed $value, array $options = []): string
    {
        $map = $this->registry->values($options['map'] ?? '');

        return (string) ($map[$value] ?? $value ?? '');
    }
}
