<?php

namespace App\Models;

use App\Traits\QueryConditionsTrait;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class CashierArrearage extends BaseModel
{
    use HasUuids, QueryConditionsTrait;

    protected $table = 'cashier_arrearage';

    protected $keyType = 'string';

    public $incrementing = false;

    protected function casts(): array
    {
        return [
            'salesman' => 'array',
            'payable' => 'float',
            'income' => 'float',
            'arrearage' => 'float',
            'amount' => 'float',
            'leftover' => 'float',
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
     * 结算科室
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * 结单人员
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 还款记录
     */
    public function details(): HasMany
    {
        return $this->hasMany(CashierArrearageDetail::class);
    }

    /**
     * 收费通知
     */
    public function cashierable(): MorphMany
    {
        return $this->morphMany(Cashier::class, 'cashierable');
    }

    /**
     * 顾客项目明细表
     */
    public function customerProduct(): HasOne
    {
        return $this->hasOne(CustomerProduct::class, 'id', 'table_id');
    }

    /**
     * 顾客物品明细表
     */
    public function customerGoods(): HasOne
    {
        return $this->hasOne(CustomerGoods::class, 'id', 'table_id');
    }
}
