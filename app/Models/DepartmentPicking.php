<?php

namespace App\Models;

use Carbon\Carbon;
use App\Traits\QueryConditionsTrait;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class DepartmentPicking extends BaseModel
{
    use QueryConditionsTrait;

    protected $table = 'department_picking';

    /**
     * 今日单据
     * @param $query
     * @return mixed
     */
    public function scopeToday($query)
    {
        return $query->whereBetween('department_picking.created_at', [
            Carbon::today(),
            Carbon::today()->endOfDay()
        ]);
    }

    /**
     * 类别
     * @return BelongsTo
     */
    public function type(): BelongsTo
    {
        return $this->belongsTo(DepartmentPickingType::class);
    }

    /**
     * 领料科室
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
     * 科室领料明细表
     * @return HasMany
     */
    public function details(): HasMany
    {
        return $this->hasMany(DepartmentPickingDetail::class, 'department_picking_id', 'id');
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
     * 领料人员
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
     * 录单人员
     * @return BelongsTo
     */
    public function createUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'create_user_id');
    }
}
