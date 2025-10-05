<?php

namespace App\Models;


class PurchaseType extends BaseModel
{
    protected $table = 'purchase_type';
    protected $guarded = [];
    protected $primaryKey = 'id';

    public static function boot()
    {
        parent::boot();

        static::saving(function ($type) {
            $type->keyword = implode(',', parse_pinyin($type->name));
        });
    }
}
