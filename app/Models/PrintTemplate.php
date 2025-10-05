<?php

namespace App\Models;

class PrintTemplate extends BaseModel
{
    protected $table = 'print_template';

    protected function casts(): array
    {
        return [
            'default' => 'boolean',
            'system'  => 'boolean',
        ];
    }
}
