<?php

namespace App\Models;

use App\Traits\QueryConditionsTrait;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Inventory extends BaseModel
{
    use QueryConditionsTrait;

    protected $table = 'inventory';

    protected function casts(): array
    {
        return [
            'amount' => 'float',
            'number' => 'float'
        ];
    }

    public static function boot(): void
    {
        parent::boot();

        static::saved(function ($inventory) {
            // 库存变动，更新goods表信息
            $inventory->goods->update([
                'inventory_number' => Inventory::query()->where('goods_id', $inventory->goods_id)->sum('number'),
                'inventory_amount' => Inventory::query()->where('goods_id', $inventory->goods_id)->sum('amount')
            ]);
        });
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
     * 仓库信息
     * @return BelongsTo
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * 商品基本单位
     * @return HasOne
     */
    public function basicUnit(): HasOne
    {
        return $this->hasOne(GoodsUnit::class, 'goods_id', 'goods_id')->where('basic', 1);
    }
}
