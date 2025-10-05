<?php

namespace App\Models;

class Supplier extends BaseModel
{
    protected $table = 'supplier';

    public static function boot(): void
    {
        parent::boot();

        static::saving(function ($supplier) {
            $supplier->keyword = implode(',', parse_pinyin($supplier->name . $supplier->short_name));
        });
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
