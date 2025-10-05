<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class CouponDetail extends BaseModel
{
    protected function casts(): array
    {
        return [
            'rate'         => 'float',
            'balance'      => 'float',
            'salesman'     => 'array',
            'integrals'    => 'float',
            'sales_price'  => 'float',
            'coupon_value' => 'float'
        ];
    }

    /**
     * 创建人员
     * @return BelongsTo
     */
    public function createUser(): BelongsTo
    {
        return $this->belongsTo(User::class);
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
     * 收费通知
     * @return MorphMany
     */
    public function cashierable(): MorphMany
    {
        return $this->morphMany(Cashier::class, 'cashierable');
    }

    /**
     * 变动历史
     * @return HasMany
     */
    public function histories(): HasMany
    {
        return $this->hasMany(CouponDetailHistory::class);
    }
}
