<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GoodsUnit extends BaseModel
{
    protected $table = 'goods_unit';

    protected function casts(): array
    {
        return [
            'prebuyprice' => 'float',
            'retailprice' => 'float'
        ];
    }

    /**
     * 获取此单位关系所属的商品
     * @return BelongsTo
     */
    public function goods(): BelongsTo
    {
        return $this->belongsTo(Goods::class, 'goods_id', 'id');
    }

    /**
     * 基本单位
     * @param $query
     * @return Builder
     */
    public function scopeBasic($query): Builder
    {
        return $query->where('basic', 1);
    }

    /**
     * 获取此单位关系对应的计量单位信息
     * @return BelongsTo
     */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'unit_id');
    }
}
