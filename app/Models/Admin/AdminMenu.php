<?php

namespace App\Models\Admin;

use App\Traits\HasTree;
use App\Models\BaseModel;

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
