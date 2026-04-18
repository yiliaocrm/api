<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashierRetailDetail extends BaseModel
{
    use HasUuids;

    protected $table = 'cashier_retail_detail';

    protected $keyType = 'string';

    public $incrementing = false;

    protected function casts(): array
    {
        return [
            'salesman' => 'array',
        ];
    }

    /**
     * 主单
     */
    public function retail(): BelongsTo
    {
        return $this->belongsTo('App\Models\CashierRetail', 'cashier_retail_id');
    }

    /**
     * 顾客信息
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo('App\Models\Customer');
    }

    /**
     * 项目信息
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * 商品信息
     */
    public function goods(): BelongsTo
    {
        return $this->belongsTo(Goods::class);
    }

    /**
     * 单位信息
     */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }
}
