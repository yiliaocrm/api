<?php

namespace App\Models;

use App\Traits\QueryConditionsTrait;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ErkaiDetail extends BaseModel
{
    use HasUuids, QueryConditionsTrait;

    protected $table = 'erkai_detail';
    protected $keyType = 'string';
    public $incrementing = false;

    protected function casts(): array
    {
        return [
            'salesman' => 'array',
            'amount'   => 'float',
            'price'    => 'float',
            'payable'  => 'float',
            'coupon'   => 'float'
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
     * 二开零购主表
     * @return BelongsTo
     */
    public function erkai(): BelongsTo
    {
        return $this->belongsTo(Erkai::class);
    }

    /**
     * 商品所有单位
     * @return HasMany
     */
    public function units(): HasMany
    {
        return $this->hasMany(GoodsUnit::class, 'goods_id', 'goods_id');
    }

    /**
     * 录单人员
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 结算科室
     * @return BelongsTo
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }
}
