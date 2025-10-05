<?php

namespace App\Models;

use App\Traits\HasTree;

/**
 * 未成交原因
 */
class Failure extends BaseModel
{
    use HasTree;

    protected $table = 'failure';
}
