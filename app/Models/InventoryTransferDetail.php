<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryTransferDetail extends BaseModel
{
    protected $table = 'inventory_transfer_detail';

    protected function casts(): array
    {
        return [
            'amount' => 'float',
            'number' => 'float',
            'price'  => 'float',
        ];
    }

    /**
     * 物品信息
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
     * 当前调拨物品单位
     * @return HasOne
     */
    public function currentUnit(): HasOne
    {
        return $this->hasOne(GoodsUnit::class, 'goods_id', 'goods_id')->where('unit_id', $this->unit_id);
    }

    /**
     * 调拨对应批次信息
     * @return BelongsTo
     */
    public function inventoryBatch(): BelongsTo
    {
        return $this->belongsTo(InventoryBatchs::class, 'inventory_batchs_id', 'id');
    }
}
