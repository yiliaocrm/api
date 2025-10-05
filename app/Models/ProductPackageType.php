<?php

namespace App\Models;

use App\Traits\HasTree;

class ProductPackageType extends BaseModel
{
    use HasTree;

    protected $table = 'product_package_type';
    protected $guarded = [];
    public $timestamps = false;
}
