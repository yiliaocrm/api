<?php

namespace App\Services\CustomerLogRemark\Contracts;

interface RemarkProfile
{
    public function labelFor(string $field): string;

    public function formatterFor(string $field): string;

    public function formatterOptionsFor(string $field): array;

    public function ignores(string $field): bool;
}
