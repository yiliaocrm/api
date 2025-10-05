<?php

namespace App\Models;

class Field extends BaseModel
{
    protected function casts(): array
    {
        return [
            'config' => 'json'
        ];
    }
}
