<?php

namespace App\Models;

use App\Traits\QueryConditionsTrait;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;


class CashierPay extends BaseModel
{
    use hasUuids, QueryConditionsTrait;

    protected $table = 'cashier_pay';

    protected function casts(): array
    {
        return [
            'income' => 'float',
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
     * 账户信息
     * @return BelongsTo
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Accounts::class, 'accounts_id');
    }

    /**
     * 收银员
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
