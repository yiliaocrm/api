<?php

namespace App\Models;

use App\Models\Product;
use App\Models\Recharge;
use App\Models\Integral;
use App\Models\GoodsUnit;
use App\Models\ErkaiDetail;
use App\Models\CustomerGoods;
use App\Models\ReceptionOrder;
use App\Models\CustomerProduct;
use App\Models\CashierArrearage;
use App\Models\SalesPerformance;
use App\Models\CashierRetailDetail;
use App\Traits\QueryConditionsTrait;
use App\Models\OutpatientPrescriptionDetail;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashierDetail extends BaseModel
{
    use HasUuids, QueryConditionsTrait;

    protected $table = 'cashier_detail';

    protected function casts(): array
    {
        return [
            'salesman'  => 'array',
            'payable'   => 'float',
            'income'    => 'float',
            'deposit'   => 'float',
            'coupon'    => 'float',
            'arrearage' => 'float'
        ];
    }

    public static function boot(): void
    {
        parent::boot();
        static::created(function ($detail) {
            // 医生门诊
            if ($detail->cashierable_type == 'App\Models\Outpatient') {
                self::handleOutpatient($detail);
            }

            // 零售收费
            if ($detail->cashierable_type == 'App\Models\CashierRetail') {
                self::handleRetail($detail);
            }
        });
    }


    /**
     * 医生门诊
     * @param $detail
     */
    public static function handleOutpatient($detail)
    {
        $prescriptionDetail = OutpatientPrescriptionDetail::query()->find($detail->table_id);
        $outpatient         = $prescriptionDetail->outpatient;
        $table_id           = null; // customer_product_id | customer_goods_id

        // 创建[顾客物品表]
        if ($detail->goods_id && !$prescriptionDetail->customer_goods_id) {
            $table_id = self::createCustomerGoods($detail, [
                'price'          => $prescriptionDetail->price,
                'consultant'     => $outpatient->consultant,
                'ek_user'        => $outpatient->ek_user,
                'doctor'         => $outpatient->doctor,
                'reception_type' => $outpatient->type,
                'medium_id'      => $outpatient->medium_id,
            ])->id;
        }

        // 项目欠款
        if ($detail->arrearage && $table_id) {
            self::createArrearageRecord($detail, $table_id);
        }

        // 创建[顾客积分]
        self::createCustomerIntegral($detail, $detail->product, $detail->goods, $detail->customer);

        // 计算业绩
        self::performance($detail, $outpatient->type);
    }

    /**
     * 零售收费
     * @param $detail
     */
    public static function handleRetail($detail)
    {
        $table_id = null;
        $order    = CashierRetailDetail::query()->find($detail->table_id);
        $retail   = $order->retail;

        // 更新[零售收费明细表]支付金额
        $order->update([
            'amount' => $detail->income + $detail->deposit
        ]);

        // 创建[项目顾客明细表]
        if ($detail->product_id) {
            $table_id = self::createCustomerProduct($detail, $order, $detail->product, [
                'reception_type' => $retail->type,
                'medium_id'      => $retail->medium_id,
            ])->id;
        }

        // 创建[顾客物品表]
        if ($detail->goods_id) {
            $table_id = self::createCustomerGoods($detail, [
                'price'          => $order->price,
                'reception_type' => $retail->type,
                'medium_id'      => $retail->medium_id,
            ])->id;
        }

        // 项目欠款
        if ($detail->arrearage && $table_id) {
            self::createArrearageRecord($detail, $table_id);
        }

        // 创建[顾客积分]
        self::createCustomerIntegral($detail, $detail->product, $detail->goods, $detail->customer);

        // 计算业绩
        self::performance($detail, $retail->type);
    }

    /**
     * 创建客户项目明细表
     * @param $detail
     * @param $order
     * @param $product
     * @param $attribute
     * @return mixed
     */
    public static function createCustomerProduct($detail, $order, $product, $attribute)
    {
        return CustomerProduct::query()->create(array_merge([
            'cashier_id'        => $detail->cashier_id,
            'cashier_detail_id' => $detail->id,
            'customer_id'       => $detail->customer_id,
            'product_id'        => $detail->product_id,
            'product_name'      => $order->product_name,
            'package_id'        => $order->package_id,
            'package_name'      => $order->package_name,
            'status'            => ($product->deduct == 0) ? 2 : 1,             // 项目状态(判断是否需要划扣)
            'expire_time'       => null,                                        // product表现在还没过期选项
            'times'             => $order->times,                               // 项目次数
            'used'              => 0,                                           // 已用次数
            'leftover'          => ($product->deduct == 0) ? 0 : $order->times, // 剩余次数
            'refund_times'      => 0,                                           // 已退次数
            'price'             => $order->price,                               // 项目原价
            'sales_price'       => $order->sales_price,                         // 执行价格
            'payable'           => $detail->payable,                            // (成交价)应收金额
            'income'            => $detail->income,                             // 实收金额(不包括余额支付)
            'deposit'           => $detail->deposit,                            // 余额支付
            'coupon'            => 0,                                           // 卡券支付
            'arrearage'         => $detail->arrearage,                          // 本单欠款金额
            'user_id'           => $detail->user_id,                            // 收银员(操作员)
            'department_id'     => $detail->department_id,                      // 结算科室
            'deduct_department' => $product->deduct_department,                 // 划扣科室
            'salesman'          => $detail->salesman
        ], $attribute));
    }

    /**
     * 顾客物品明细表
     * @param $detail
     * @param $attribute
     * @return mixed
     */
    public static function createCustomerGoods($detail, $attribute)
    {
        // 转换为最小单位
        $baseUnit    = GoodsUnit::basic()->where('goods_id', $detail->goods_id)->first();
        $currentUnit = GoodsUnit::query()->where('unit_id', $detail->unit_id)->where('goods_id', $detail->goods_id)->first();
        $unit_id     = $detail->unit_id;
        $number      = $detail->times;

        // 转换为最小单位
        if ($baseUnit->unit_id != $detail->unit_id) {
            $number  = bcmul($detail->times, $currentUnit->rate, 4);
            $unit_id = $baseUnit->unit_id;
        }

        return CustomerGoods::query()->create(array_merge([
            'customer_id'       => $detail->customer_id,
            'cashier_id'        => $detail->cashier_id,
            'cashier_detail_id' => $detail->id,
            'cashierable_type'  => $detail->cashierable_type,
            'table_name'        => $detail->table_name,
            'table_id'          => $detail->table_id,
            'goods_id'          => $detail->goods_id,
            'goods_name'        => $detail->goods_name,
            'specs'             => $detail->specs,
            'package_id'        => $detail->package_id,
            'package_name'      => $detail->package_name,
            'status'            => 1,
            'number'            => $number,
            'unit_id'           => $unit_id,
            'unit_name'         => get_unit_name($unit_id),                     // 单位名称(后续需要给cashier_detail增加上冗余字段)
            'used'              => 0,                                           // 已用数量
            'leftover'          => $number,
            'refund_times'      => 0,
            'payable'           => $detail->payable,                            // (成交价)应收金额
            'income'            => $detail->income,                             // 实收金额(不包括余额支付)
            'deposit'           => $detail->deposit,                            // 余额支付
            'coupon'            => 0,                                           // 卡券支付
            'arrearage'         => $detail->arrearage,                          // 本单欠款金额
            'user_id'           => $detail->user_id,                            // 收银员(操作员)
            'department_id'     => $detail->department_id,
            'salesman'          => $detail->salesman
        ], $attribute));
    }

    /**
     * 写入欠款记录
     * @param $detail
     * @param $table_id customer_product|customer_goods
     */
    public static function createArrearageRecord($detail, $table_id)
    {
        $unit_id = $detail->unit_id;
        $times   = $detail->times;

        // 物品信息
        if ($detail->goods_id) {
            $baseUnit    = GoodsUnit::basic()->where('goods_id', $detail->goods_id)->first();
            $currentUnit = GoodsUnit::query()->where('unit_id', $detail->unit_id)->where('goods_id', $detail->goods_id)->first();

            // 转换为最小单位
            if ($baseUnit->unit_id != $detail->unit_id) {
                $times   = bcmul($detail->times, $currentUnit->rate, 4);
                $unit_id = $baseUnit->unit_id;
            }
        }

        CashierArrearage::query()->create([
            'cashier_id'          => $detail->cashier_id,
            'customer_id'         => $detail->customer_id,
            'status'              => 1, // 还款中
            'package_id'          => $detail->package_id,
            'package_name'        => $detail->package_name,
            'product_id'          => $detail->product_id,
            'product_name'        => $detail->product_name,
            'goods_id'            => $detail->goods_id,
            'goods_name'          => $detail->goods_name,
            'times'               => $times,
            'unit_id'             => $unit_id,
            'specs'               => $detail->specs,
            'table_id'            => $table_id,
            'payable'             => $detail->payable,
            'income'              => $detail->income,
            'arrearage'           => $detail->arrearage,
            'leftover'            => $detail->arrearage,
            'amount'              => 0,
            'salesman'            => $detail->salesman,
            'department_id'       => $detail->department_id,
            'last_repayment_time' => null,
            'user_id'             => user()->id
        ]);
    }

    /**
     * 顾客消费积分
     * @param $detail
     * @param $product
     * @param $goods
     * @param $customer
     */
    public static function createCustomerIntegral($detail, $product, $goods, $customer)
    {
        $rate         = parameter('cywebos_integral_rate');                                                  // 积分比例
        $integral     = ($detail->income + $detail->deposit) * $rate;                                         // 计算当前项目积分
        $integralable = $detail->product_id ? $product->integral : $goods->integral;                         // 项目|物品 是否开启积分
        $type         = $detail->goods_id ? 3 : (($detail->product_id && $detail->product_id == 1) ? 1 : 2); // 积分类型:1、充值 2、项目 3、物品

        $remark = [
            1 => "充值金额：{$detail->income}",
            2 => $integral > 0 ? "消费项目：{$detail->product_name}\r\n实收金额:{$detail->income}\r\n余额支付：{$detail->deposit}" : "项目退款：{$detail->product_name}\r\n实收金额:{$detail->income}",
            3 => $integral > 0 ? "购买商品：{$detail->goods_name}\r\n实收金额:{$detail->income}\r\n余额支付：{$detail->deposit}" : "购买退款：{$detail->goods_name}\r\n实收金额:{$detail->income}",
        ];

        // 项目|物品 没有开启积分 或 系统关闭积分功能
        if (!$integralable || !parameter('cywebos_integral_enable')) {
            Integral::query()->create([
                'customer_id' => $customer->id,
                'type'        => $type,
                'type_id'     => $detail->cashier_id,    // 业务单号
                'before'      => $customer->integral,    // 原有积分
                'integral'    => 0,                        // 变动积分
                'after'       => $customer->integral,    // 现有积分
                'remark'      => $remark[$type] . '没有开启积分',
                'data'        => $detail
            ]);
        } else {
            Integral::query()->create([
                'customer_id' => $customer->id,
                'type'        => $type,
                'type_id'     => $detail->cashier_id,                // 业务单号
                'before'      => $customer->integral,               // 原有积分
                'integral'    => $integral,                         // 变动积分
                'after'       => $customer->integral + $integral,   // 现有积分
                'remark'      => $remark[$type],
                'data'        => $detail
            ]);
            $customer->update([
                'integral' => $customer->integral + $integral
            ]);
        }
    }

    /**
     * 销售业绩(开发人和销售人)
     * @param $detail
     * @param $reception_type
     */
    public static function performance($detail, $reception_type)
    {
        $product                    = $detail->product;
        $goods                      = $detail->goods;
        $customer                   = $detail->customer;
        $cashier                    = $detail->cashier;
        $sales_commission           = $detail->product_id ? $product->commission : $goods->commission;    // 项目|物品 是否提成
        $recharge_commission        = Product::query()->find(1)->commission;    // 预收费用 是否提成
        $sales_commission_amount    = $sales_commission ? $detail->income : 0; // 实收提成
        $recharge_commission_amount = !$recharge_commission ? $detail->deposit : 0;  // 余款支付提成

        // 销售人员提成
        if (is_array($detail->salesman) && count($detail->salesman)) {
            foreach ($detail->salesman as $v) {
                $num1   = ($sales_commission_amount * $v['rate']) / 100;
                $num2   = ($recharge_commission_amount * $v['rate']) / 100;
                $remark = "实收金额:{$detail->income}计提金额:{$num1},余额支付:{$detail->deposit}计提金额{$num2}";
                SalesPerformance::query()->create([
                    'cashier_id'     => $detail->cashier_id,
                    'customer_id'    => $detail->customer_id,
                    'position'       => 1,
                    'table_name'     => $cashier->cashierable_type,
                    'table_id'       => $cashier->cashierable_id,
                    'user_id'        => $v['user_id'],
                    'reception_type' => $reception_type,
                    'package_id'     => $detail->package_id,
                    'package_name'   => $detail->package_name,
                    'product_id'     => $detail->product_id,
                    'product_name'   => $detail->product_name,
                    'goods_id'       => $detail->goods_id,
                    'goods_name'     => $detail->goods_name,
                    'payable'        => $detail->payable,
                    'income'         => $detail->income,
                    'arrearage'      => $detail->arrearage,
                    'deposit'        => $detail->deposit,
                    'amount'         => (($sales_commission_amount + $recharge_commission_amount) * $v['rate']) / 100,
                    'rate'           => $v['rate'],
                    'remark'         => $remark
                ]);
            }
        }

        // 开发人员
        if ($customer->ascription) {
            $remark = "实收金额:{$detail->income}计提金额:{$sales_commission_amount},余额支付:{$detail->deposit}计提金额{$recharge_commission_amount}";
            SalesPerformance::query()->create([
                'cashier_id'     => $detail->cashier_id,
                'customer_id'    => $detail->customer_id,
                'position'       => 2,
                'table_name'     => $cashier->cashierable_type,
                'table_id'       => $cashier->cashierable_id,
                'user_id'        => $customer->ascription,
                'reception_type' => $reception_type,
                'package_id'     => $detail->package_id,
                'package_name'   => $detail->package_name,
                'product_id'     => $detail->product_id,
                'product_name'   => $detail->product_name,
                'goods_id'       => $detail->goods_id,
                'goods_name'     => $detail->goods_name,
                'payable'        => $detail->payable,
                'income'         => $detail->income,
                'arrearage'      => $detail->arrearage,
                'deposit'        => $detail->deposit,
                'amount'         => $sales_commission_amount + $recharge_commission_amount,
                'rate'           => 100,
                'remark'         => $remark
            ]);
        }


    }

    /**
     * 服务提成
     * @param $detail
     * @param $customerProduct
     * @return bool
     */
    public static function servicePerformance($detail, $customerProduct)
    {
        // 欠款的划扣记录
        $treatments = $customerProduct->treatments()->where('arrearage', '<>', 0)->get();
        // 收费记录
        $cashier = $detail->cashier;
        // 实收金额+存款支付
        $amount = $detail->income + $detail->deposit;

        if ($amount <= 0) {
            return false;
        }

        foreach ($treatments as $treatment) {
            $_amount = ($amount > $treatment->arrearage) ? $treatment->arrearage : $amount;

            // 配台人员
            if (!empty($treatment->participants)) {
                foreach ($treatment->participants as $participant) {
                    SalesPerformance::query()->create([
                        'customer_id'    => $detail->customer_id,
                        'position'       => 3,
                        'table_name'     => $cashier->cashierable_type,
                        'table_id'       => $cashier->cashierable_id,
                        'user_id'        => $participant['user_id'],
                        'reception_type' => $customerProduct->reception_type,
                        'package_id'     => $detail->package_id,
                        'package_name'   => $detail->package_name,
                        'product_id'     => $detail->product_id,
                        'product_name'   => $detail->product_name,
                        'goods_id'       => null,
                        'goods_name'     => null,
                        'payable'        => $detail->payable,
                        'income'         => $detail->income,
                        'arrearage'      => $detail->arrearage,
                        'deposit'        => $detail->deposit,
                        'amount'         => ($_amount * $participant['rate']) / 100,
                        'rate'           => $participant['rate'],
                        'remark'         => '项目还款'
                    ]);
                }
            }

            // 扣减总金额
            $amount -= $_amount;

            // 更新划扣记录
            $treatment->update([
                'arrearage' => $treatment->arrearage - $_amount
            ]);
        }
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
     * 物品信息
     * @return BelongsTo
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * 商品信息
     * @return BelongsTo
     */
    public function goods(): BelongsTo
    {
        return $this->belongsTo(Goods::class);
    }

    /**
     * 收费单主单信息
     * @return BelongsTo
     */
    public function cashier(): BelongsTo
    {
        return $this->belongsTo(Cashier::class);
    }

    /**
     * 顾客项目明细表
     * @return BelongsTo
     */
    public function customerProduct(): BelongsTo
    {
        return $this->belongsTo(CustomerProduct::class, 'table_id');
    }

    /**
     * 顾客物品明细表
     * @return BelongsTo
     */
    public function customerGoods(): BelongsTo
    {
        return $this->belongsTo(CustomerGoods::class, 'table_id');
    }

    /**
     * 计量单位信息
     * @return BelongsTo
     */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    /**
     * 收银员
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 结算科室
     * @return BelongsTo
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }
}
