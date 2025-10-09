<?php

namespace App\Models\Admin;

use App\Models\BaseModel;
use App\Traits\HasTree;

class Menu extends BaseModel
{
    use HasTree;

    protected function casts(): array
    {
        return [
            'meta'             => 'array',
            'permission_scope' => 'json',
        ];
    }

    protected static function nameField(): string
    {
        return 'title';
    }
}
