<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class CustomerWechat extends BaseModel
{
    protected $guarded = [];

    /**
     * 顾客信息
     * @return BelongsTo
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * 顾客日志
     * @return MorphMany
     */
    public function customerLog(): MorphMany
    {
        return $this->morphMany(CustomerLog::class, 'logable');
    }
}
