<?php

namespace App\Models;

use App\Traits\QueryConditionsTrait;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Consumable extends BaseModel
{
    use QueryConditionsTrait;

    protected $table = 'consumable';

    /**
     * 今日单据
     *
     * @return mixed
     */
    public function scopeToday($query)
    {
        return $query->whereBetween('consumable.created_at', [
            Carbon::today(),
            Carbon::today()->endOfDay(),
        ]);
    }

    /**
     * 顾客信息
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * 出库仓库
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * 领料科室
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * 领料人员
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 录单人员
     */
    public function createUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'create_user_id');
    }

    /**
     * 用料登记明细表
     */
    public function details(): HasMany
    {
        return $this->hasMany(ConsumableDetail::class);
    }

    /**
     * 库存变动明细
     */
    public function inventoryDetail(): MorphMany
    {
        return $this->morphMany(InventoryDetail::class, 'detailable');
    }
}
