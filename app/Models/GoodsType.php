<?php

namespace App\Models;

use App\Traits\HasTree;

class GoodsType extends BaseModel
{
    use HasTree;

    protected $table = 'goods_type';
    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'editable'   => 'boolean',
            'deleteable' => 'boolean',
        ];
    }
}
