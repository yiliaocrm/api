<?php

namespace App\Models;

use App\Enums\CouponDetailStatus;
use App\Traits\QueryConditionsTrait;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class CouponDetail extends BaseModel
{
    use QueryConditionsTrait;

    protected function casts(): array
    {
        return [
            'status' => CouponDetailStatus::class,
            'rate' => 'float',
            'balance' => 'float',
            'salesman' => 'array',
            'integrals' => 'float',
            'sales_price' => 'float',
            'coupon_value' => 'float',
        ];
    }

    protected function statusText(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->status?->getLabel(),
        );
    }

    /**
     * 创建人员
     */
    public function createUser(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 顾客信息
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * 收费通知
     */
    public function cashierable(): MorphMany
    {
        return $this->morphMany(Cashier::class, 'cashierable');
    }

    /**
     * 变动历史
     */
    public function histories(): HasMany
    {
        return $this->hasMany(CouponDetailHistory::class);
    }
}
