<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Treatment extends BaseModel
{
    use HasUuids;

    protected $table = 'treatment';
    protected $keyType = 'string';
    public $incrementing = false;

    protected function casts(): array
    {
        return [
            'participants' => 'array',
        ];
    }

    /**
     * 顾客信息
     * @return BelongsTo
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * 顾客项目明细表
     * @return HasOne
     */
    public function customerProduct(): HasOne
    {
        return $this->hasOne(CustomerProduct::class, 'id', 'customer_product_id');
    }

    /**
     * 配台人员
     * @return HasMany
     */
    public function treatmentParticipants(): HasMany
    {
        return $this->hasMany(TreatmentParticipants::class);
    }

    /**
     * 业绩表
     * @return HasMany
     */
    public function salesPerformance(): HasMany
    {
        return $this->hasMany(SalesPerformance::class, 'table_id');
    }

    /**
     * 划扣人员
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
