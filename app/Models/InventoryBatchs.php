<?php

namespace App\Models;

use App\Traits\QueryConditionsTrait;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryBatchs extends BaseModel
{
    use QueryConditionsTrait;

    protected $table = 'inventory_batchs';

    protected function casts(): array
    {
        return [
            'amount' => 'float',
            'number' => 'float',
            'price'  => 'float'
        ];
    }

    public static function boot(): void
    {
        parent::boot();

        static::created(function ($batchs) {
            self::updateOrCreateInventory($batchs);
        });

        static::updated(function ($batchs) {
            self::updateInventory($batchs);
        });
    }

    /**
     * 仓库信息
     * @return BelongsTo
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * 库存批次多态关系
     * @return MorphTo
     */
    public function batchable(): MorphTo
    {
        return $this->morphTo();
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
     * 商品基本单位
     * @return HasOne
     */
    public function basicUnit(): HasOne
    {
        return $this->hasOne(GoodsUnit::class, 'goods_id', 'goods_id')->where('basic', 1);
    }

    /**
     * 当前批次商品信息
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
     * 库存信息
     * @return HasOne
     */
    public function inventory(): HasOne
    {
        return $this->hasOne(Inventory::class, 'warehouse_id', 'warehouse_id')->where('goods_id', $this->goods_id);
    }

    /**
     * 创建或者更新库存信息
     * @param $batchs
     */
    public static function updateOrCreateInventory($batchs): void
    {
        $inventory = $batchs->inventory()->firstOrNew([
            'warehouse_id' => $batchs->warehouse_id,
            'goods_id'     => $batchs->goods_id,
        ]);

        // 更新
        $inventory->number = bcadd($inventory->number, $batchs->number, 4);
        $inventory->amount = bcadd($inventory->amount, $batchs->amount, 4);

        $inventory->save();
    }

    /**
     * 更新库存表
     * @param $batchs
     */
    public static function updateInventory($batchs): void
    {
        $original    = $batchs->getRawOriginal();
        $number_diff = $batchs->number - $original['number'];
        $amount_diff = $batchs->amount - $original['amount'];

        // 加上差额
        $batchs->inventory->update([
            'number' => bcadd($batchs->inventory->number, $number_diff, 4),
            'amount' => bcadd($batchs->inventory->amount, $amount_diff, 4)
        ]);
    }
}
