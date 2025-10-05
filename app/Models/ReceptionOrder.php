<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReceptionOrder extends BaseModel
{
    use HasUuids;

    protected $table = 'reception_order';
    protected $keyType = 'string';
    public $incrementing = false;

    protected function casts(): array
    {
        return [
            'salesman'    => 'array',
            'amount'      => 'float',
            'price'       => 'float',
            'sales_price' => 'float',
            'payable'     => 'float'
        ];
    }

    /**
     * 产品表
     * @return BelongsTo
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * 商品信息
     * @return BelongsTo
     */
    public function goods(): BelongsTo
    {
        return $this->belongsTo(Goods::class);
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
     * 接待信息
     * @return BelongsTo
     */
    public function reception(): BelongsTo
    {
        return $this->belongsTo(Reception::class);
    }

    /**
     * 顾客信息
     * @return BelongsTo
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
