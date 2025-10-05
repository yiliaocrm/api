<?php

namespace App\Models;

use App\Traits\QueryConditionsTrait;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashierRefundDetail extends BaseModel
{
    use QueryConditionsTrait;

    protected $table = 'cashier_refund_detail';

    protected function casts(): array
    {
        return [
            'amount'   => 'float',
            'salesman' => 'array'
        ];
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
     * 收费人员
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 顾客项目明细表
     * @return BelongsTo
     */
    public function customerProduct(): BelongsTo
    {
        return $this->belongsTo(CustomerProduct::class);
    }

    /**
     * 顾客物品明细表
     * @return BelongsTo
     */
    public function customerGoods(): BelongsTo
    {
        return $this->belongsTo(CustomerGoods::class);
    }
}
