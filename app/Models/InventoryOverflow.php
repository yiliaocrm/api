<?php

namespace App\Models;

use Carbon\Carbon;
use App\Traits\QueryConditionsTrait;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class InventoryOverflow extends BaseModel
{
    use QueryConditionsTrait;

    /**
     * 报溢单明细
     * @return HasMany
     */
    public function details(): HasMany
    {
        return $this->hasMany(InventoryOverflowDetail::class);
    }

    /**
     * 报溢科室
     * @return BelongsTo
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
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
     * 经办人员
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 审核人员
     * @return BelongsTo
     */
    public function checkUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'check_user');
    }

    /**
     * 录单人员
     * @return BelongsTo
     */
    public function createUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'create_user_id');
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
     * 库存批次记录
     * @return MorphMany
     */
    public function inventoryBatch(): MorphMany
    {
        return $this->morphMany(InventoryBatchs::class, 'batchable');
    }

    /**
     * 今日单据
     * @param $query
     * @return mixed
     */
    public function scopeToday($query)
    {
        return $query->whereBetween('inventory_overflows.created_at', [
            Carbon::today(),
            Carbon::today()->endOfDay()
        ]);
    }
}
