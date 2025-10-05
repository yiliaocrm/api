<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Coupon extends BaseModel
{
    protected function casts(): array
    {
        return [
            'integrals'    => 'float',
            'sales_price'  => 'float',
            'coupon_value' => 'float',
            'multiple_use' => 'boolean'
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
     * 发放明细
     * @return HasMany
     */
    public function details(): HasMany
    {
        return $this->hasMany(CouponDetail::class);
    }
}
