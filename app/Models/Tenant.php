<?php

namespace App\Models;

use DateTimeInterface;
use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Concerns\HasDomains;

class Tenant extends BaseTenant implements TenantWithDatabase
{
    use HasDatabase, HasDomains;

    protected $keyType = 'string';
    protected $hidden = ['data'];

    /**
     * 自定义列
     * @return string[]
     */
    public static function getCustomColumns(): array
    {
        return [
            'id',
            'status',
            'expire_date',
            'name',
            'version',
            'remark'
        ];
    }

    /**
     * 为数组 / JSON 序列化准备日期
     * @param DateTimeInterface $date
     * @return string
     */
    protected function serializeDate(DateTimeInterface $date): string
    {
        return $date->format($this->dateFormat ?: 'Y-m-d H:i:s');
    }
}
