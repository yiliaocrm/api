<?php

namespace App\Models;

class Department extends BaseModel
{
    protected $table = 'department';
    protected $appends = ['permission'];

    protected function casts(): array
    {
        return [
            'disabled' => 'boolean',
            'primary'  => 'boolean',
        ];
    }

    public static function boot(): void
    {
        parent::boot();
        static::saving(function ($department) {
            $department->keyword = implode(',', parse_pinyin($department->name));
        });
    }

    public function getPermissionAttribute()
    {
        return $this->attributes['permission'] = 'department' . $this->id;
    }

    public static function getInfo($id)
    {
        if (!$id) {
            return false;
        }

        static $_info = [];

        if (!isset($_info[$id])) {
            $_info[$id] = static::find($id);
        }

        return $_info[$id];
    }
}
