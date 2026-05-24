<?php

namespace App\Models;

use App\Traits\QueryConditionsTrait;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class RetailOutbound extends BaseModel
{
    use QueryConditionsTrait;

    protected $table = 'retail_outbound';

    protected function casts(): array
    {
        return [
            'amount' => 'float',
        ];
    }

    /**
     * 出料明细
     */
    public function details(): HasMany
    {
        return $this->hasMany(RetailOutboundDetail::class);
    }

    /**
     * 出料明细的商品单位
     */
    public function detailsWithUnits(): HasMany
    {
        return $this->hasMany(RetailOutboundDetail::class)->with(['goodsUnits']);
    }

    /**
     * 顾客信息
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * 出料仓库
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * 出料科室
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * 出料人员
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * 创建人员
     */
    public function createUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'create_user_id');
    }

    /**
     * 库存变动明细
     */
    public function inventoryDetail(): MorphMany
    {
        return $this->morphMany(InventoryDetail::class, 'detailable');
    }

    /**
     * 今日单据
     *
     * @return mixed
     */
    public function scopeToday($query)
    {
        return $query->whereBetween('retail_outbound.created_at', [
            Carbon::today(),
            Carbon::today()->endOfDay(),
        ]);
    }
}
