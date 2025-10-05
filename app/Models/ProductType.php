<?php

namespace App\Models;

use App\Traits\HasTree;

class ProductType extends BaseModel
{
    use HasTree;

    protected $table = 'product_type';
    protected $guarded = [];
    public $timestamps = false;
}
