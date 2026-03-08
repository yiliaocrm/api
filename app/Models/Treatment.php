<?php

namespace App\Models;

use App\Enums\TreatmentStatus;
use App\Traits\QueryConditionsTrait;
use App\Traits\WorkflowTrait;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Treatment extends BaseModel
{
    use HasUuids, QueryConditionsTrait, WorkflowTrait;

    protected $table = 'treatment';

    protected function casts(): array
    {
        return [
            'status' => TreatmentStatus::class,
            'participants' => 'array',
        ];
    }

    /**
     * 顾客信息
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * 执行科室
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * 顾客项目明细表
     */
    public function customerProduct(): HasOne
    {
        return $this->hasOne(CustomerProduct::class, 'id', 'customer_product_id');
    }

    /**
     * 配台人员
     */
    public function treatmentParticipants(): HasMany
    {
        return $this->hasMany(TreatmentParticipants::class);
    }

    /**
     * 业绩表
     */
    public function salesPerformance(): HasMany
    {
        return $this->hasMany(SalesPerformance::class, 'table_id');
    }

    /**
     * 划扣人员
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 项目信息
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * 状态文字
     */
    protected function statusText(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->status?->getLabel(),
        );
    }
}
