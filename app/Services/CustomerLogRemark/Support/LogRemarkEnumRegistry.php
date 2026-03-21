<?php

namespace App\Services\CustomerLogRemark\Support;

class LogRemarkEnumRegistry
{
    public function values(string $map): array
    {
        return match ($map) {
            'customer.sex' => [
                1 => '男',
                2 => '女',
            ],
            'reservation.status' => [
                0 => '未保存',
                1 => '未上门',
                2 => '已到院',
            ],
            default => [],
        };
    }
}
