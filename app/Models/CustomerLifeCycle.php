<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class CustomerLifeCycle extends BaseModel
{
    use HasUuids;

    protected $table = 'customer_life_cycle';
    protected $keyType = 'string';
    public $incrementing = false;


    public function cycle(): MorphTo
    {
        return $this->morphTo();
    }
}
