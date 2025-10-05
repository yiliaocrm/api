<?php

namespace App\Models;

use App\Traits\HasTree;

class Item extends BaseModel
{
    use HasTree;

    protected $table = 'item';
    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'parentid' => 'integer',
        ];
    }
}
