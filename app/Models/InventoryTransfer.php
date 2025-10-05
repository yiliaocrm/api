<?php

namespace App\Models;

use Carbon\Carbon;
use App\Traits\QueryConditionsTrait;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class InventoryTransfer extends BaseModel
{
    use QueryConditionsTrait;

    protected $table = 'inventory_transfer';

    /**
     * 获取出库仓库关联
     */
    public function outWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'out_warehouse_id', 'id');
    }

    /**
     * 获取入库仓库关联
     */
    public function inWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'in_warehouse_id', 'id');
    }

    /**
     * 录入人员
     * @return BelongsTo
     */
    public function createUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'create_user_id', 'id');
    }

    /**
     * 审核人员
     * @return BelongsTo
     */
    public function checkUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'check_user', 'id');
    }

    /**
     * 经办人员
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }


    /**
     * 调拨明细表
     * @return HasMany
     */
    public function details(): HasMany
    {
        return $this->hasMany(InventoryTransferDetail::class);
    }

    /**
     * 调拨入库对应的批次
     * @return MorphMany
     */
    public function inventoryBatch(): MorphMany
    {
        return $this->morphMany(InventoryBatchs::class, 'batchable');
    }

    /**
     * 库存变动明细
     * @return MorphMany
     */
    public function inventoryDetail(): MorphMany
    {
        return $this->morphMany(InventoryDetail::class, 'detailable');
    }

    /**
     * 今日单据
     * @param $query
     * @return mixed
     */
    public function scopeToday($query)
    {
        return $query->whereBetween('inventory_transfer.created_at', [
            Carbon::today(),
            Carbon::today()->endOfDay()
        ]);
    }
}
