<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashierRetailDetail extends BaseModel
{
    use HasUuids;

    protected $table = 'cashier_retail_detail';
    protected $keyType = 'string';
    public $incrementing = false;

    protected function casts(): array
    {
        return [
            'salesman' => 'array',
        ];
    }

    /**
     * 主单
     * @return BelongsTo
     */
    public function retail(): BelongsTo
    {
        return $this->belongsTo('App\Models\CashierRetail', 'cashier_retail_id');
    }

    /**
     * 顾客信息
     * @return BelongsTo
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo('App\Models\Customer');
    }
}
