<?php

namespace App\Models;

use App\Traits\QueryConditionsTrait;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesPerformance extends BaseModel
{
    use HasUuids, QueryConditionsTrait;

    protected $table = 'sales_performance';
    protected $keyType = 'string';
    public $incrementing = false;

    protected function casts(): array
    {
        return [
            'data'      => 'array',
            'income'    => 'float',
            'amount'    => 'float',
            'arrearage' => 'float',
            'deposit'   => 'float',
            'payable'   => 'float',
        ];
    }

    /**
     * 计提人员
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 顾客信息
     * @return BelongsTo
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
