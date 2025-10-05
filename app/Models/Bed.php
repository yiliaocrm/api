<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Bed extends BaseModel
{
    protected $table = 'bed';

    /**
     * 所属门店
     * @return BelongsTo
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }
}
