<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Consumable extends BaseModel
{
    protected $table = 'consumable';

    /**
     * 今日单据
     * @param $query
     * @return mixed
     */
    public function scopeToday($query)
    {
        return $query->whereBetween('consumable.created_at', [
            Carbon::today(),
            Carbon::today()->endOfDay()
        ]);
    }

    /**
     * 顾客信息
     * @return BelongsTo
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * 用料登记明细表
     * @return HasMany
     */
    public function details(): HasMany
    {
        return $this->hasMany(ConsumableDetail::class);
    }

    /**
     * 库存变动明细
     * @return MorphMany
     */
    public function inventoryDetail(): MorphMany
    {
        return $this->morphMany(InventoryDetail::class, 'detailable');
    }
}
