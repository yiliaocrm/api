<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class CashierArrearage extends BaseModel
{
    use HasUuids;

    protected $table = 'cashier_arrearage';
    protected $keyType = 'string';
    public $incrementing = false;

    protected function casts(): array
    {
        return [
            'salesman'  => 'array',
            'payable'   => 'float',
            'income'    => 'float',
            'arrearage' => 'float',
            'amount'    => 'float',
            'leftover'  => 'float'
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
     * 还款记录
     * @return HasMany
     */
    public function details(): HasMany
    {
        return $this->hasMany(CashierArrearageDetail::class);
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
     * 顾客项目明细表
     * @return HasOne
     */
    public function customerProduct(): HasOne
    {
        return $this->hasOne(CustomerProduct::class, 'id', 'table_id');
    }

    /**
     * 顾客物品明细表
     * @return HasOne
     */
    public function customerGoods(): HasOne
    {
        return $this->hasOne(CustomerGoods::class, 'id', 'table_id');
    }
}
