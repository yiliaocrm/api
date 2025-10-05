<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\QueryConditionsTrait;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerGoods extends BaseModel
{
    use QueryConditionsTrait, HasUuids;

    protected $table = 'customer_goods';
    protected $keyType = 'string';
    public $incrementing = false;

    protected function casts(): array
    {
        return [
            'price'          => 'float',
            'income'         => 'float',
            'deposit'        => 'float',
            'payable'        => 'float',
            'arrearage'      => 'float',
            'coupon'         => 'float',
            'salesman'       => 'array',
            'invoice_amount' => 'float',
        ];
    }

    /**
     * 收费单
     * @return BelongsTo
     */
    public function cashier(): BelongsTo
    {
        return $this->belongsTo(Cashier::class);
    }

    /**
     * 营收明细表
     * @return BelongsTo
     */
    public function cashierDetail(): BelongsTo
    {
        return $this->belongsTo(CashierDetail::class);
    }

    /**
     * 商品对应单位
     * @return HasMany
     */
    public function units(): HasMany
    {
        return $this->hasMany(GoodsUnit::class, 'goods_id', 'goods_id');
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
     * 关联部门
     * @return BelongsTo
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id', 'id');
    }

    /**
     * 媒介来源
     * @return BelongsTo
     */
    public function medium(): BelongsTo
    {
        return $this->belongsTo(Medium::class, 'medium_id', 'id');
    }

    /**
     * 收费人员
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /**
     * 现场咨询
     * @return BelongsTo
     */
    public function consultantUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'consultant', 'id');
    }

    /**
     * 助诊医生
     * @return BelongsTo
     */
    public function doctorUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'doctor', 'id');
    }

    /**
     * 二开人员
     * @return BelongsTo
     */
    public function ekUserRelation(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ek_user', 'id');
    }

    /**
     * 接诊类型
     * @return BelongsTo
     */
    public function receptionTypeRelation(): BelongsTo
    {
        return $this->belongsTo(ReceptionType::class, 'reception_type', 'id');
    }

    /**
     * 商品对应的库存批次
     * @return HasMany
     */
    public function inventoryBatchs(): HasMany
    {
        return $this->hasMany(InventoryBatchs::class, 'goods_id', 'goods_id');
    }
}
