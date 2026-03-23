<?php

namespace App\Models;

use App\Traits\QueryConditionsTrait;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryCheck extends BaseModel
{
    use QueryConditionsTrait;

    /**
     * 盘点明细
     */
    public function details(): HasMany
    {
        return $this->hasMany(InventoryCheckDetail::class);
    }

    /**
     * 仓库信息
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * 科室信息
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * 经办人员
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 审核人员
     */
    public function checkUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'check_user');
    }

    /**
     * 录单人员
     */
    public function createUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'create_user_id');
    }

    /**
     * 报损单
     */
    public function inventoryLoss(): BelongsTo
    {
        return $this->belongsTo(InventoryLoss::class);
    }

    /**
     * 报溢单
     */
    public function inventoryOverflow(): BelongsTo
    {
        return $this->belongsTo(InventoryOverflow::class);
    }
}
