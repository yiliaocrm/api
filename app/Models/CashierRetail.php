<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class CashierRetail extends BaseModel
{
    use HasUuids;

    protected $table = 'cashier_retail';
    protected $keyType = 'string';
    public $incrementing = false;

    protected function casts(): array
    {
        return [
            'detail' => 'array',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
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
