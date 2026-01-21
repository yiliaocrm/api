<?php

namespace App\Models;

use Carbon\Carbon;
use Ramsey\Uuid\Uuid;
use App\Models\GoodsUnit;
use App\Traits\QueryConditionsTrait;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Cashier extends BaseModel
{
    use QueryConditionsTrait;

    protected $table = 'cashier';
    protected $keyType = 'string';
    public $incrementing = false;

    protected function casts(): array
    {
        return [
            'arrearage' => 'float',
            'deposit'   => 'float',
            'income'    => 'float',
            'coupon'    => 'float',
            'payable'   => 'float',
        ];
    }

    public static function boot(): void
    {
        parent::boot();

        static::creating(function ($cashier) {
            $cashier->id  = Uuid::uuid7()->toString();
            $cashier->key = date('Ymd') . str_pad((static::today()->count() + 1), 4, '0', STR_PAD_LEFT);
        });

        static::saved(function ($cashier) {
            // 医生门诊单收费
            if ($cashier->status == 2 && $cashier->cashierable_type == 'App\Models\Outpatient') {
                self::handleOutpatient($cashier);
            }

            // 零售收费
            if ($cashier->status == 2 && $cashier->cashierable_type == 'App\Models\CashierRetail') {
                self::handleCashierRetail($cashier);
            }
        });
    }

    /**
     * 医生门诊收费
     * @param $cashier
     */
    public static function handleOutpatient($cashier)
    {
        // 1、更新{医生门诊}状态
        $cashier->cashierable()->update(['status' => 2]);

        // 本次收费,总付款金额
        $income = $cashier->pay()->where('accounts_id', '<>', 1)->sum('income');

        // 本次(项目/物品)消费金额,排除预收费
        $payable = $cashier->details()->where(function ($query) {
            $query->where('product_id', '<>', 1)->orWhereNull('product_id');
        })->sum('payable');

        // 2、预收费用
        $ysf = $cashier->details()->where(function ($query) {
            $query->where('product_id', 1);
        })->sum('income');

        // 3、余额支付，扣减账户余额
        $balance = $cashier->pay()->where('accounts_id', 1)->sum('income');

        // 4、更新{西药处方状态}
        $cashier->cashierable->prescriptions()->whereIn('id',
            collect($cashier->detail['prescriptions'])->pluck('id')->toArray()
        )->update(['status' => 2]);

        // 5、更新顾客(付款)信息
        $customer = $cashier->customer;
        $customer->update([
            'total_payment' => $customer->total_payment + $income,
            'amount'        => $customer->amount + $payable,
            'balance'       => $customer->balance + $ysf - $balance,
            'arrearage'     => $customer->arrearage + $cashier->arrearage
        ]);
    }

    /**
     * 零售收费
     * @param $cashier
     */
    public static function handleCashierRetail($cashier)
    {
        $customer = $cashier->customer;

        // 本次收费,总付款金额
        $income = $cashier->pay()->where('accounts_id', '<>', 1)->sum('income');

        // 本次(项目/物品)消费金额,排除预收费
        $payable = $cashier->details()->where(function ($query) {
            $query->where('product_id', '<>', 1)->orWhereNull('product_id');
        })->sum('payable');

        // 3、预收费用
        $ysf = $cashier->details()->where(function ($query) {
            $query->where('product_id', 1);
        })->sum('income');

        // 4、余额支付，扣减账户余额
        $balance = $cashier->pay()->where('accounts_id', 1)->sum('income');

        // 更新顾客(付款)信息
        $customer->update([
            'total_payment' => $customer->total_payment + $income,
            'amount'        => $customer->amount + $payable,
            'balance'       => $customer->balance + $ysf - $balance,
            'arrearage'     => $customer->arrearage + $cashier->arrearage
        ]);
    }

    public static function getInfo($id)
    {
        if (!$id) {
            return false;
        }

        static $_info = [];

        if (!isset($_info[$id])) {
            $_info[$id] = static::find($id);
        }

        return $_info[$id];
    }

    /**
     * 录单人员
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 结单人员
     * @return BelongsTo
     */
    public function operatorUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'operator');
    }

    /**
     * 收费对应的业务表
     * @return MorphTo
     */
    public function cashierable(): MorphTo
    {
        return $this->morphTo();
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
     * 付款账户明细(后续删除)
     * @return HasMany
     */
    public function pay(): HasMany
    {
        return $this->hasMany(CashierPay::class);
    }

    /**
     * 付款账户明细
     * @return HasMany
     */
    public function pays(): HasMany
    {
        return $this->hasMany(CashierPay::class);
    }

    /**
     * 券支付明细
     * @return HasMany
     */
    public function cashierCoupon(): HasMany
    {
        return $this->hasMany(CashierCoupon::class);
    }

    /**
     * 顾客已购卡券列表
     * @return HasMany
     */
    public function customerCouponDetail(): HasMany
    {
        return $this->hasMany(CouponDetail::class, 'customer_id', 'customer_id');
    }

    /**
     * 营收明细
     * @return HasMany
     */
    public function details(): HasMany
    {
        return $this->hasMany(CashierDetail::class);
    }

    /**
     * 卡券金额变动明细表
     * @return MorphMany
     */
    public function couponDetailHistory(): MorphMany
    {
        return $this->morphMany(CouponDetailHistory::class, 'historyable');
    }

    /**
     * 设置detail字段
     * @param $value
     */
    public function setDetailAttribute($value)
    {
        $this->attributes['detail'] = json_encode($value);
    }

    /**
     * 访问detail
     * @param $value
     * @return mixed
     */
    public function getDetailAttribute($value)
    {
        $detail = json_decode($value, true);

        // 现场咨询单,需要处理{商品单位}
        if ($this->cashierable_type == 'App\Models\Consultant') {
            $detail = collect($detail)->map(function ($rows) {
                if ($rows['unit_id']) {
                    $rows['units'] = GoodsUnit::where('goods_id', $rows['goods_id'])->get();
                }
                return $rows;
            });
        }

        return $detail;
    }

    /**
     * 今日单据
     * @param $query
     * @return mixed
     */
    public function scopeToday($query)
    {
        return $query->whereBetween('cashier.created_at', [
            Carbon::today(),
            Carbon::today()->endOfDay()
        ]);
    }
}
