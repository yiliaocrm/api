<?php

namespace App\Models;

use App\Enums\CashierRetailStatus;
use App\Traits\QueryConditionsTrait;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class CashierRetail extends BaseModel
{
    use HasUuids, QueryConditionsTrait;

    protected $table = 'cashier_retail';

    protected $keyType = 'string';

    public $incrementing = false;

    protected function casts(): array
    {
        return [
            'status' => CashierRetailStatus::class,
        ];
    }

    protected function statusText(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->status?->getLabel(),
        );
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function cashierable(): MorphMany
    {
        return $this->morphMany(Cashier::class, 'cashierable');
    }

    public function pay(): HasMany
    {
        return $this->hasMany(CashierPay::class, 'cashier_id', 'cashier_id');
    }

    public function details(): HasMany
    {
        return $this->hasMany(CashierRetailDetail::class);
    }
}
