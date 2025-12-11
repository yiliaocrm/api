<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OperationLog extends BaseModel
{
    protected function casts(): array
    {
        return [
            'user_id'     => 'integer',
            'duration'    => 'decimal:2',
            'status_code' => 'integer',
            'params'      => 'array',
        ];
    }

    /**
     * 关联操作用户
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
