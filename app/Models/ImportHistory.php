<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
class ImportHistory extends BaseModel
{
    protected $table = 'import_history';

    const int UN_START = 0;
    const int SUCCESS = 1;
    const int FAIL = 2;

    /**
     * 关联导入模板
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(ImportTemplate::class);
    }

    public function importHeader(): Attribute
    {
        return Attribute::make(
            get: fn($value) => json_decode($value, true),
        );
    }
}
