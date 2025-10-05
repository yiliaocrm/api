<?php

namespace App\Models;

class DepartmentPickingType extends BaseModel
{
    public static function boot(): void
    {
        parent::boot();
        static::saving(fn($model) => $model->keyword = implode(',', parse_pinyin($model->name)));
    }

    public static function getInfo($id)
    {
        if (!$id) {
            return false;
        }

        static $_info = [];

        if (!isset($_info[$id])) {
            $_info[$id] = static::query()->find($id);
        }

        return $_info[$id];
    }
}
