<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Str;

class ImportTemplate extends BaseModel
{
    /**
     * 导入类
     */
    public function useImport(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => app(Str::of($value)->startsWith('App\Imports') ? $value : 'App\\Imports\\'.$value)
        );
    }

    /**
     * 模板路径转换
     */
    public function template(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => url($value)
        );
    }
}
