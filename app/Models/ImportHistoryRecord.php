<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

/**
 * @property $id
 * @property $history_id
 * @property $row_data
 * @property $status
 * @property $error_msg
 * @property $create_user_id
 * @property $created_at
 * @property $updated_at
 */
class ImportHistoryRecord extends Model
{
    //
    protected $table = 'import_history_records';

    protected $fillable = [
        'id',
        'history_id',
        'row_data',
        'status',
        'error_msg',
        'create_user_id',
        'created_at',
        'updated_at'
    ];


    const int UN_START = 0;
    const int SUCCESS = 1;
    const int FAIL = 2;

    /**
     * 保存行数据
     *
     * @return Attribute
     */
    public function rowData(): Attribute
    {
        return Attribute::make(
            get: fn($value) => json_decode($value, true),

            set: fn($value) => json_encode($value, JSON_UNESCAPED_UNICODE),
        );
    }

    /**
     * 保存错误信息
     *
     * @return Attribute
     */
    public function errorMsg(): Attribute
    {
        return Attribute::make(
            get: fn($value) => json_decode($value, true),

            set: fn($value) => json_encode($value, JSON_UNESCAPED_UNICODE),
        );
    }
}
