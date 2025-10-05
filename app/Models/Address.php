<?php

namespace App\Models;

use App\Traits\HasTree;

class Address extends BaseModel
{
    use HasTree;

    protected $table = 'address';
    public $timestamps = false;
}
