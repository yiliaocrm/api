<?php

namespace App\Models;

use Carbon\Carbon;
use App\Traits\QueryConditionsTrait;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class PurchaseReturn extends BaseModel
{
    use QueryConditionsTrait;

    protected $table = 'purchase_return';

    /**
     * 今日单据
     * @param $query
     * @return mixed
     */
    public function scopeToday($query)
    {
        return $query->whereBetween('purchase_return.created_at', [
            Carbon::today(),
            Carbon::today()->endOfDay()
        ]);
    }

    /**
     * 退货明细
     * @return HasMany
     */
    public function details(): HasMany
    {
        return $this->hasMany(PurchaseReturnDetail::class);
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
    public function auditor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'check_user');
    }

    /**
     * 创建人员
     * @return BelongsTo
     */
    public function createUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'create_user_id');
    }

    /**
     * 审核人员
     * @return BelongsTo
     */
    public function checkUserRelation(): BelongsTo
    {
        return $this->belongsTo(User::class, 'check_user');
    }
}
