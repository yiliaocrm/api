<?php

namespace App\Models;

use Carbon\Carbon;
use App\Traits\QueryConditionsTrait;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;


class Purchase extends BaseModel
{
    protected $table = 'purchase';

    protected function casts(): array
    {
        return [
            'amount' => 'float',
        ];
    }

    use QueryConditionsTrait;

    /**
     * 进货明细表
     * @return HasMany
     */
    public function details(): HasMany
    {
        return $this->hasMany(PurchaseDetail::class);
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
     * 供应商
     * @return BelongsTo
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * 进货仓库
     * @return BelongsTo
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * 采购类别
     * @return BelongsTo
     */
    public function type(): BelongsTo
    {
        return $this->belongsTo(PurchaseType::class);
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
    public function checkUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'check_user');
    }

    /**
     * 今日单据
     * @param $query
     * @return mixed
     */
    public function scopeToday($query)
    {
        return $query->whereBetween('created_at', [
            Carbon::today(), Carbon::today()->endOfDay()
        ]);
    }
}
