<?php

namespace App\Models;

use App\Enums\CashierInvoiceType;
use App\Traits\QueryConditionsTrait;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CashierInvoice extends BaseModel
{
    use QueryConditionsTrait;

    protected $appends = ['type_text'];

    protected function casts(): array
    {
        return [
            'amount' => 'float',
            'type' => CashierInvoiceType::class,
        ];
    }

    /**
     * 开票类型文本
     */
    public function getTypeTextAttribute(): string
    {
        return $this->type?->getLabel() ?? '';
    }

    /**
     * 开票明细表
     */
    public function details(): HasMany
    {
        return $this->hasMany(CashierInvoiceDetail::class, 'cashier_invoice_id', 'id');
    }

    /**
     * 顾客信息
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id', 'id');
    }

    /**
     * 创建人信息
     */
    public function createUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'create_user_id', 'id');
    }
}
