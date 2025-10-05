<?php

namespace App\Models;

use DateTimeInterface;
use Cartalyst\Sentinel\Roles\EloquentRole;

class Role extends EloquentRole
{
    protected $fillable = [
        'name',
        'slug',
        'execution',
        'permissions'
    ];

    protected function casts(): array
    {
        return [
            'execution'   => 'boolean',
            'permissions' => 'array',
        ];
    }

    public static function boot(): void
    {
        parent::boot();

        static::saving(function ($user) {
            $user->keyword = implode(',', array_merge([$user->slug], parse_pinyin($user->name)));
        });
    }

    // public function setPermissionsAttribute(array $permissions)
    // {
    //     $this->attributes['permissions'] = $permissions ?
    //         json_encode(array_map(function($key){
    //             return boolval($key);
    //         }, $permissions))
    //     : '';
    // }

    /**
     * 为数组 / JSON 序列化准备日期
     * @param DateTimeInterface $date
     * @return string
     */
    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format($this->dateFormat ?: 'Y-m-d H:i:s');
    }
}
