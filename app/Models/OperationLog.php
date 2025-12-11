<?php

namespace App\Models;

class OperationLog extends BaseModel
{
    protected function casts(): array
    {
        return [
            'user_id'     => 'integer',
            'duration'    => 'decimal:2',
            'status_code' => 'integer',
        ];
    }
}
