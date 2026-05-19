<?php

namespace App\Models;

use App\Enums\ErkaiStatus;
use App\Traits\QueryConditionsTrait;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Erkai extends BaseModel
{
    use HasUuids, QueryConditionsTrait;

    protected $table = 'erkai';

    protected $keyType = 'string';

    public $incrementing = false;

    protected function casts(): array
    {
        return [
            'status' => ErkaiStatus::class,
            'payable' => 'float',
            'income' => 'float',
            'deposit' => 'float',
            'coupon' => 'float',
            'arrearage' => 'float',
        ];
    }

    protected function statusText(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->status?->getLabel(),
        );
    }

    /**
     * 顾客信息
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * 明细
     */
    public function details(): HasMany
    {
        return $this->hasMany(ErkaiDetail::class);
    }

    /**
     * 收费通知
     */
    public function cashierable(): MorphMany
    {
        return $this->morphMany(Cashier::class, 'cashierable');
    }

    /**
     * 二开科室
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * 录单人员
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 媒介来源
     */
    public function medium(): BelongsTo
    {
        return $this->belongsTo(Medium::class);
    }
}
