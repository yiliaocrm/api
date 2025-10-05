<?php

namespace App\Models;

use App\Traits\HasTree;

class AdminMenu extends BaseModel
{
    use HasTree;

    protected function casts(): array
    {
        return [
            'meta' => 'array',
        ];
    }

    protected static function nameField(): string
    {
        return 'title';
    }
}
