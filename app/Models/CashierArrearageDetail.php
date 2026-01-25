<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashierArrearageDetail extends BaseModel
{
    protected $table = 'cashier_arrearage_detail';

    protected function casts(): array
    {
        return [
            'salesman' => 'array',
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
     * 欠款单
     * @return BelongsTo
     */
    public function cashierArrearage(): BelongsTo
    {
        return $this->belongsTo(CashierArrearage::class);
    }

    /**
     * 结算科室
     * @return BelongsTo
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * 结单人员
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
