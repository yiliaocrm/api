<?php

namespace App\Models;

class ReceptionType extends BaseModel
{
    protected $table = 'reception_type';

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
