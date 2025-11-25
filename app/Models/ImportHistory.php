<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

/**
 * @property $id
 * @property $template_id
 * @property $import_header
 * @property $file_name
 * @property $file_path
 * @property $file_size
 * @property $file_type
 * @property $status
 * @property $total_rows
 * @property $success_rows
 * @property $fail_rows
 * @property $create_user_id
 * @property $created_at
 * @property $updated_at
 */
class ImportHistory extends Model
{
    //
    protected $table = 'import_history';

    protected $fillable = [
        'id',
        'template_id',
        'import_header',
        'file_name',
        'file_path',
        'file_size',
        'file_type',
        'status',
        'total_rows',
        'success_rows',
        'fail_rows',
        'create_user_id',
        'created_at',
        'updated_at'
    ];


    const int UN_START = 0;
    const int SUCCESS = 1;
    const int FAIL = 2;

    public function importHeader(): Attribute
    {
        return Attribute::make(
            get: fn($value) => json_decode($value, true),
        );
    }
}
