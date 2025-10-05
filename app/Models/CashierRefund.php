<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class CashierRefund extends BaseModel
{
    use HasUuids;

    protected $table = 'cashier_refund';
    protected $keyType = 'string';
    public $incrementing = false;

    protected function casts(): array
    {
        return [
            'amount' => 'float',
            'detail' => 'array'
        ];
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
     * 退款明细
     * @return HasMany
     */
    public function details(): HasMany
    {
        return $this->hasMany(CashierRefundDetail::class);
    }
}
