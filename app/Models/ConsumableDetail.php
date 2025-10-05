<?php

namespace App\Models;

use App\Traits\QueryConditionsTrait;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConsumableDetail extends BaseModel
{
    use QueryConditionsTrait;

    protected $table = 'consumable_detail';

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
     * 当前退货物品单位
     * @return HasOne
     */
    public function currentUnit(): HasOne
    {
        return $this->hasOne(GoodsUnit::class, 'goods_id', 'goods_id')->where('unit_id', $this->unit_id);
    }

    /**
     * 生产厂家
     * @return BelongsTo
     */
    public function manufacturer(): BelongsTo
    {
        return $this->belongsTo(Manufacturer::class);
    }

    /**
     * 用料登记单
     * @return BelongsTo
     */
    public function consumable(): BelongsTo
    {
        return $this->belongsTo(Consumable::class);
    }

    /**
     * 领料科室
     * @return BelongsTo
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * 出料仓库
     * @return BelongsTo
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
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
     * 领料对应的批次
     * @return HasOne
     */
    public function inventoryBatch(): HasOne
    {
        return $this->hasOne(InventoryBatchs::class, 'id', 'inventory_batchs_id');
    }
}
