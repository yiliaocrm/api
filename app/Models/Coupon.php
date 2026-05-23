<?php

namespace App\Models;

use App\Traits\QueryConditionsTrait;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Coupon extends BaseModel
{
    use QueryConditionsTrait;

    protected function casts(): array
    {
        return [
            'integrals' => 'float',
            'sales_price' => 'float',
            'coupon_value' => 'float',
            'multiple_use' => 'boolean',
        ];
    }

    /**
     * 创建人员
     */
    public function createUser(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 发放明细
     */
    public function details(): HasMany
    {
        return $this->hasMany(CouponDetail::class);
    }
}
