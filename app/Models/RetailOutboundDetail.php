<?php

namespace App\Models;

use App\Traits\QueryConditionsTrait;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RetailOutboundDetail extends BaseModel
{
    use QueryConditionsTrait;

    protected $table = 'retail_outbound_detail';

    protected function casts(): array
    {
        return [
            'price'  => 'float',
            'amount' => 'float'
        ];
    }

    /**
     * 物品库存批次(多个)
     * @return HasMany
     */
    public function inventoryBatchs(): HasMany
    {
        return $this->hasMany(InventoryBatchs::class, 'goods_id', 'goods_id');
    }

    /**
     * 出库对应的批次
     * @return HasOne
     */
    public function inventoryBatch(): HasOne
    {
        return $this->hasOne(InventoryBatchs::class, 'id', 'inventory_batchs_id');
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
     * 商品基本单位
     * @return HasOne
     */
    public function basicUnit(): HasOne
    {
        return $this->hasOne(GoodsUnit::class, 'goods_id', 'goods_id')->where('basic', 1);
    }

    /**
     * 当前物品单位
     * @return HasOne
     */
    public function currentUnit(): HasOne
    {
        return $this->hasOne(GoodsUnit::class, 'goods_id', 'goods_id')->where('unit_id', $this->unit_id);
    }

    /**
     * 顾客已购物品表
     * @return HasOne
     */
    public function customerGoods(): HasOne
    {
        return $this->hasOne(CustomerGoods::class, 'id', 'customer_goods_id');
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
     * 出料科室
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
     * 出料人员
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
