<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\MorphTo;


class CustomerTalk extends BaseModel
{
    use HasUuids;

    protected $table = 'customer_talk';
    protected $keyType = 'string';
    public $incrementing = false;

    public function talk(): MorphTo
    {
        return $this->morphTo();
    }
}
