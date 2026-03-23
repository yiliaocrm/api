<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryCheckDetail extends BaseModel
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'book_number' => 'float',
            'actual_number' => 'float',
            'diff_number' => 'float',
            'price' => 'float',
            'diff_amount' => 'float',
        ];
    }

    /**
     * 盘点主单
     */
    public function inventoryCheck(): BelongsTo
    {
        return $this->belongsTo(InventoryCheck::class);
    }

    /**
     * 商品信息
     */
    public function goods(): BelongsTo
    {
        return $this->belongsTo(Goods::class);
    }

    /**
     * 库存批次
     */
    public function inventoryBatch(): BelongsTo
    {
        return $this->belongsTo(InventoryBatchs::class, 'inventory_batchs_id');
    }
}
