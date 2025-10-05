<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseReturnDetail extends BaseModel
{
    protected $table = 'purchase_return_detail';

    protected function casts(): array
    {
        return [
            'amount' => 'float',
            'number' => 'float',
            'price'  => 'float',
        ];
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
     * 商品对应单位信息
     * @return HasMany
     */
    public function goodsUnits(): HasMany
    {
        return $this->hasMany(GoodsUnit::class, 'goods_id', 'goods_id');
    }

    /**
     * 基本单位
     * @return HasOne
     */
    public function basicUnit(): HasOne
    {
        return $this->hasOne(GoodsUnit::class, 'goods_id', 'goods_id')->where('basic', 1);
    }

    /**
     * 当前退货物品单位
     * @return HasOne
     */
    public function currentUnit(): HasOne
    {
        return $this->hasOne(GoodsUnit::class, 'goods_id', 'goods_id')->where('unit_id', $this->unit_id);
    }

    /**
     * 库存可用批次
     * @return HasMany
     */
    public function inventoryBatchs(): HasMany
    {
        return $this->hasMany(InventoryBatchs::class, 'goods_id', 'goods_id');
    }

    /**
     * 退货对应的批次
     * @return HasOne
     */
    public function inventoryBatch(): HasOne
    {
        return $this->hasOne(InventoryBatchs::class, 'id', 'inventory_batchs_id');
    }
}
