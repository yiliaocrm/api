<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OutpatientPrescriptionDetail extends BaseModel
{
    use HasUuids;

    protected $table = 'outpatient_prescription_detail';
    protected $keyType = 'string';
    public $incrementing = false;

    /**
     * 接诊记录
     * @return BelongsTo
     */
    public function outpatient(): BelongsTo
    {
        return $this->belongsTo(Outpatient::class, 'reception_id');
    }

    /**
     * 获取商品可用库存批次
     * @return HasMany
     */
    public function inventoryBatch(): HasMany
    {
        return $this->hasMany(InventoryBatchs::class, 'goods_id', 'goods_id')
            ->where('number', '>', 0)
            ->orderBy('created_at', 'asc');
    }
}
