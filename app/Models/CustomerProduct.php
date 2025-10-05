<?php

namespace App\Models;

use Ramsey\Uuid\Uuid;
use App\Traits\QueryConditionsTrait;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerProduct extends BaseModel
{
    use QueryConditionsTrait;

    protected $table = 'customer_product';
    protected $keyType = 'string';
    public $incrementing = false;

    protected function casts(): array
    {
        return [
            'arrearage'      => 'float',
            'price'          => 'float',
            'sales_price'    => 'float',
            'income'         => 'float',
            'deposit'        => 'float',
            'payable'        => 'float',
            'salesman'       => 'array',
            'invoice_amount' => 'float',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($product) {
            $product->id = Uuid::uuid7()->toString();
        });

        static::updating(function ($product) {
            // 剩余次数不为0
            if ($product->leftover) {
                $product->status = 5;
            }
            // 项目次数 等于 剩余次数 (主要是撤销治疗记录)
            if ($product->times == $product->leftover) {
                $product->status = 1;
            }
            // 退款 && 购买次数 = 退款次数
            if ($product->isDirty('refund_times') && $product->times == $product->refund_times) {
                $product->status = 3;
            }
            // 项目做完了,非退款
            if ($product->leftover == 0 && !$product->isDirty('refund_times')) {
                $product->status = 2;
            }
        });
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
     * 媒介来源
     * @return BelongsTo
     */
    public function medium(): BelongsTo
    {
        return $this->belongsTo(Medium::class);
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
     * 划扣记录
     * @return HasMany
     */
    public function treatments(): HasMany
    {
        return $this->hasMany(Treatment::class);
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
     * 项目信息
     * @return BelongsTo
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }
}
