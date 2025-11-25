<?php

namespace App\Models;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Casts\Attribute;

/**
 * @property $id
 * @property $title
 * @property $async_limit
 * @property $template
 * @property $chunk_size
 * @property $use_import
 * @property $create_user_id
 * @property $created_at
 * @property $updated_at
 */
class ImportTemplate extends BaseModel
{
    //
    protected $table = 'import_templates';

    protected $fillable = [
        'id',
        'title',
        'async_limit',
        'template',
        'chunk_size',
        'use_import',
        'create_user_id',
        'created_at',
        'updated_at'
    ];

    /**
     * 导入类
     *
     * @return Attribute
     */
    public function useImport(): Attribute
    {
        return Attribute::make(
            get: fn($value) => app(Str::of($value)->startsWith('App\Imports') ? $value : 'App\\Imports\\' . $value)
        );
    }
}
