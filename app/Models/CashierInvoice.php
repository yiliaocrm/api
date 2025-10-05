<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashierInvoice extends BaseModel
{
    protected function casts(): array
    {
        return [
            'amount' => 'float',
        ];
    }

    /**
     * 开票哦明细表
     * @return HasMany
     */
    public function details(): HasMany
    {
        return $this->hasMany(CashierInvoiceDetail::class, 'cashier_invoice_id', 'id');
    }

    /**
     * 顾客信息
     * @return BelongsTo
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id', 'id');
    }

    /**
     * 创建人信息
     * @return BelongsTo
     */
    public function createUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'create_user_id', 'id');
    }
}
