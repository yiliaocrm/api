<?php

namespace App\Models;

use App\Enums\CashierRefundStatus;
use App\Traits\QueryConditionsTrait;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class CashierRefund extends BaseModel
{
    use HasUuids, QueryConditionsTrait;

    protected $table = 'cashier_refund';

    protected $keyType = 'string';

    public $incrementing = false;

    protected function casts(): array
    {
        return [
            'amount' => 'float',
            'detail' => 'array',
            'status' => CashierRefundStatus::class,
        ];
    }

    /**
     * 顾客信息
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * 收费人员
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 收费通知
     */
    public function cashierable(): MorphMany
    {
        return $this->morphMany(Cashier::class, 'cashierable');
    }

    /**
     * 退款明细
     */
    public function details(): HasMany
    {
        return $this->hasMany(CashierRefundDetail::class);
    }

    /**
     * 状态文本
     */
    protected function statusText(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->status?->getLabel(),
        );
    }
}
