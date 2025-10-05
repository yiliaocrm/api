<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerItem extends BaseModel
{
    /**
     * 获取拥有该咨询项目的顾客
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * 获取咨询项目的基础信息
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    /**
     * 获取拥有该项目记录的模型 (Reservation 或 Reception).
     */
    public function itemable(): MorphTo
    {
        return $this->morphTo();
    }
}
