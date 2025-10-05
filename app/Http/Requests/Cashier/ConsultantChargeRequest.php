<?php

namespace App\Http\Requests\Cashier;

use Carbon\Carbon;
use App\Models\Product;
use App\Models\Cashier;
use App\Models\Integral;
use App\Models\GoodsUnit;
use App\Models\CouponDetail;
use App\Models\CustomerGoods;
use App\Models\ReceptionItems;
use App\Models\ReceptionOrder;
use App\Models\CustomerProduct;
use App\Models\CashierArrearage;
use App\Models\SalesPerformance;
use App\Models\CustomerDepositDetail;
use Illuminate\Foundation\Http\FormRequest;

class ConsultantChargeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'id'                                => [
                'required',
                function ($attribute, $cashier_id, $fail) {
                    $pay     = collect($this->input('pay'));
                    $detail  = collect($this->input('detail'));
                    $coupon  = collect($this->input('cashier_coupon'));
                    $cashier = Cashier::query()->find($cashier_id);

                    if (!$cashier) {
                        return $fail('没有找到收费单记录!');
                    }

                    if ($cashier->status !== 1) {
                        return $fail('业务状态错误!');
                    }

                    if ($detail->where('product_id', 1)->count() > 1) {
                        return $fail('【预收费用】重复!');
                    }

                    if ($detail->where('product_id', 1)->count() && !$pay->count()) {
                        return $fail('【预收费用】项目必须收费!');
                    }

                    // 预收费用 大于 实收费用
                    if ($detail->where('product_id', 1)->count() && $detail->where('product_id', 1)->sum('payable') > $pay->where('accounts_id', '<>', 1)->sum('income')) {
                        return $fail('【实收金额】必须大于【预收费用】!');
                    }

                    // 余额支付 大于 账户实际余额
                    if ($pay->where('accounts_id', 1)->sum('income') > $cashier->customer->balance) {
                        return $fail('账户余额不够支付');
                    }

                    // [卡券支付]+[组合支付] > [应付金额]
                    if (bccomp(bcadd($pay->sum('income'), $coupon->sum('income'), 4), $detail->sum('payable'), 4) > 0) {
                        return $fail('[卡券支付]+[组合支付]不能大于[合计应收]');
                    }
                }
            ],
            'detail'                            => 'required|array',
            'detail.*.product_id'               => [
                'nullable',
                function ($attribute, $product_id, $fail) {
                    $product = Product::query()->find($product_id);
                    if (!$product) {
                        return $fail("{$product->name}没有找到项目信息!");
                    }
                    if ($product->disabled) {
                        return $fail("{$product->name}项目已禁用!");
                    }
                }
            ],
            'pay'                               => 'nullable|array',
            'pay.*.accounts_id'                 => 'required|distinct|exists:accounts,id',
            'pay.*.income'                      => 'required|numeric|gt:0',
            'cashier_coupon'                    => 'nullable|array',
            'cashier_coupon.*.coupon_id'        => 'required|exists:coupons,id',
            'cashier_coupon.*.coupon_detail_id' => [
                'required',
                function ($attribute, $coupon_detail_id, $fail) {
                    $income       = $this->input(str_replace('coupon_detail_id', 'income', $attribute));
                    $couponDetail = CouponDetail::query()->find($coupon_detail_id);
                    if (!$couponDetail) {
                        return $fail('没有找到卡券信息!');
                    }
                    if ($couponDetail->balance < $income) {
                        return $fail("卡券余额仅剩下{$couponDetail->balance}");
                    }
                    // 判断卡券是否一次性使用等
                }
            ],

        ];
    }

    public function messages(): array
    {
        return [
            'id.required'                                => '缺少id参数',
            'pay.*.accounts_id.distinct'                 => '收款账户不能重复!',
            'pay.*.income.required'                      => '[收款金额]不能为空!',
            'pay.*.income.gt'                            => '[收款金额]不能为0',
            'cashier_coupon.*.coupon_detail_id.required' => '[卡券id]不能为空!',
        ];
    }

    /**
     * 付款信息
     * @param $customer_id
     * @return array
     */
    public function payData($customer_id): array
    {
        $data = [];
        $pay  = $this->input('pay');

        foreach ($pay as $p) {
            $data[] = [
                'customer_id' => $customer_id,
                'accounts_id' => $p['accounts_id'],
                'income'      => $p['income'],
                'remark'      => $p['remark'] ?? null,
                'user_id'     => user()->id
            ];
        }

        return $data;
    }

    /**
     * 使用卡券
     * @param $customer_id
     * @return array
     */
    public function cashierCouponData($customer_id): array
    {
        $data    = [];
        $coupons = $this->input('cashier_coupon');

        foreach ($coupons as $coupon) {
            $data[] = [
                'coupon_id'        => $coupon['coupon_id'],
                'coupon_detail_id' => $coupon['coupon_detail_id'],
                'coupon_name'      => $coupon['coupon_name'],
                'coupon_number'    => $coupon['coupon_number'],
                'customer_id'      => $customer_id,
                'income'           => $coupon['income'],
                'remark'           => $coupon['remark'],
                'user_id'          => user()->id
            ];
        }

        return $data;
    }

    /**
     * [营收明细]数据
     * @param $cashier
     * @return array
     */
    public function CashierDetailData($cashier): array
    {
        $data     = [];
        $detail   = collect($cashier->detail)->sortBy('product_id');    // 按项目排序
        $paycount = collect($cashier->pay)->where('accounts_id', '<>', 1)->sum('income');   // 实收金额(不包括余额支付)
        $balance  = collect($cashier->pay)->where('accounts_id', 1)->sum('income'); // 余额支付费用
        $coupon   = collect($cashier->cashierCoupon)->sum('income');    // 卡券支付
        $amount   = $paycount + $balance + $coupon;   // 组合支付:实收、余额、卡券

        // 费用摊到各个项目上
        foreach ($detail as $k => $v) {
            $insertData = [
                'customer_id'      => $cashier->customer_id,
                'cashierable_type' => $cashier->cashierable_type,
                'table_name'       => 'reception_order',
                'table_id'         => $v['id'],
                'package_id'       => $v['package_id'] ?? null,
                'package_name'     => $v['package_name'] ?? null,
                'product_id'       => $v['product_id'] ?? null,
                'product_name'     => $v['product_name'] ?? null,
                'goods_id'         => $v['goods_id'] ?? null,
                'goods_name'       => $v['goods_name'] ?? null,
                'times'            => $v['times'] ?? null,
                'unit_id'          => $v['unit_id'] ?? null,
                'specs'            => $v['specs'] ?? null,
                'payable'          => $v['payable'],
                'income'           => 0,    // 实收
                'coupon'           => 0,    // 卡券支付
                'deposit'          => 0,    // 余额支付
                'arrearage'        => 0,    // 欠款金额
                'department_id'    => $v['department_id'],
                'salesman'         => $v['salesman'],
                'user_id'          => user()->id,
            ];

            // 实收支付
            $insertData['income'] = ($paycount > $v['payable']) ? $v['payable'] : $paycount;

            // 余额支付
            $depositDiff = $v['payable'] - $insertData['income'];
            if ($depositDiff > 0) {
                $insertData['deposit'] = $balance > $depositDiff ? $depositDiff : $balance;
            }

            // 券额支付
            $couponDiff = $v['payable'] - $insertData['income'] - $insertData['deposit'];
            if ($couponDiff > 0) {
                $insertData['coupon'] = $coupon > $couponDiff ? $couponDiff : $coupon;
            }

            // 欠款
            $insertData['arrearage'] = $v['payable'] - $insertData['income'] - $insertData['deposit'] - $insertData['coupon'];

            // 自减
            $paycount -= $insertData['income'];
            $balance  -= $insertData['deposit'];
            $coupon   -= $insertData['coupon'];
            $amount   -= ($insertData['income'] + $insertData['deposit'] + $insertData['coupon']);

            // 赋值
            $data[] = $insertData;
        }

        return $data;
    }

    /**
     * 更新收费通知单
     * @param $cashier
     * @return array
     */
    public function cashierData($cashier): array
    {
        $income    = collect($cashier->pay)->where('accounts_id', '<>', 1)->sum('income');
        $deposit   = collect($cashier->pay)->where('accounts_id', 1)->sum('income');
        $coupon    = collect($cashier->cashierCoupon)->sum('income');
        $arrearage = $cashier->payable - $income - $deposit - $coupon;
        $detail    = $cashier->cashierable->orders()->whereIn('id', collect($cashier->detail)->pluck('id'))->get();

        return [
            'status'    => 2,
            'income'    => $income,
            'deposit'   => $deposit,
            'coupon'    => $coupon,
            'arrearage' => $arrearage,
            'operator'  => user()->id,
            'detail'    => $detail
        ];
    }

    /**
     * 获取收银员修改的数据
     * @param $cashier
     * @return array
     */
    public function getDetailChanges($cashier): array
    {
        $inserted = [];
        $deleted  = [];
        $updated  = [];

        // 被删除的数据ids
        $deleted = collect($cashier->detail)->pluck('id')->diff(
            collect($this->input('detail'))->pluck('id')->filter()->all()
        )->toArray();

        // 新增数据
        $attachNew = collect($this->input('detail'))->filter(function ($item) {
            return !isset($item['id']);
        })->toArray();

        foreach ($attachNew as $k) {
            $inserted[] = [
                'customer_id'   => $cashier->customer_id,
                'status'        => 2, // 待收费
                'type'          => $k['type'],
                'package_id'    => $k['package_id'] ?? null,
                'package_name'  => $k['package_name'] ?? null,
                'splitable'     => isset($k['splitable']) ? $k['splitable'] : null,
                'product_id'    => $k['product_id'] ?? null,
                'product_name'  => $k['product_name'] ?? null,
                'goods_id'      => $k['goods_id'] ?? null,
                'goods_name'    => $k['goods_name'] ?? null,
                'times'         => $k['times'],
                'unit_id'       => $k['unit_id'] ?? null,
                'specs'         => $k['specs'] ?? null,
                'price'         => $k['price'],
                'sales_price'   => $k['sales_price'],
                'payable'       => $k['payable'],
                'amount'        => 0,
                'department_id' => $k['department_id'],
                'salesman'      => $k['salesman'],
                'remark'        => $k['remark'] ?? null,
                'user_id'       => user()->id,
            ];
        }

        // 修改数据
        $attachUpdate = collect($this->input('detail'))->filter(function ($item) {
            return isset($item['id']);
        })->toArray();

        foreach ($attachUpdate as $order) {
            $updated[] = [
                'id'            => $order['id'],
                'reception_id'  => $order['reception_id'],
                'customer_id'   => $order['customer_id'],
                'status'        => 2,
                'type'          => $order['type'],
                'package_id'    => $order['package_id'] ?? null,
                'package_name'  => $order['package_name'] ?? null,
                'splitable'     => isset($order['splitable']) ? $order['splitable'] : null,
                'product_id'    => $order['product_id'] ?? null,
                'product_name'  => $order['product_name'] ?? null,
                'goods_id'      => $order['goods_id'] ?? null,
                'goods_name'    => $order['goods_name'] ?? null,
                'times'         => $order['times'],
                'unit_id'       => $order['unit_id'] ?? null,
                'specs'         => $order['specs'] ?? null,
                'price'         => $order['price'],
                'sales_price'   => $order['sales_price'],
                'payable'       => $order['payable'],
                'amount'        => 0,
                'department_id' => $order['department_id'],
                'salesman'      => $order['salesman'],
                'remark'        => $order['remark'] ?? null,
                'user_id'       => $order['user_id'],
            ];
        }

        return [
            'inserted' => $inserted,
            'deleted'  => $deleted,
            'updated'  => $updated
        ];
    }


    /**
     * 收费项目明细处理
     * @param $cashierDetail
     * @param $customer
     */
    public function handleCashierDetail($cashierDetail, $customer)
    {
        // 更新现场咨询开单表
        $order     = ReceptionOrder::query()->find($cashierDetail->table_id);
        $reception = $order->reception;
        $table_id  = null; // customer_product_id | customer_goods_id

        // 后续加入券支付
        $order->update([
            'status' => 3, // 成交
            'amount' => $cashierDetail->income + $cashierDetail->deposit,
            'coupon' => $cashierDetail->coupon
        ]);

        // 创建[项目顾客明细表]
        if ($cashierDetail->product_id) {
            $this->updateReceptionItems($cashierDetail, $reception);
            $table_id = $this->createCustomerProduct($cashierDetail, $order, $reception);
        }

        // [充值] 或 [余额支付], 创建[预收费用变动明细记录]
        if ($cashierDetail->product_id == 1 || $cashierDetail->deposit) {
            $after = ($cashierDetail->product_id == 1) ? bcadd($customer->balance, $cashierDetail->income, 4) : bcsub($customer->balance, $cashierDetail->deposit, 4);
            CustomerDepositDetail::query()->create([
                'customer_id'       => $cashierDetail->customer_id,
                'cashier_id'        => $cashierDetail->cashier_id,
                'cashier_detail_id' => $cashierDetail->id,
                'before'            => $customer->balance,
                'balance'           => ($cashierDetail->product_id == 1) ? $cashierDetail->income : -1 * $cashierDetail->deposit,
                'after'             => $after,
                'cashierable_type'  => $cashierDetail->cashierable_type,
                'table_name'        => $cashierDetail->table_name,
                'table_id'          => $cashierDetail->table_id,
                'package_id'        => $cashierDetail->package_id,
                'package_name'      => $cashierDetail->package_name,
                'product_id'        => $cashierDetail->product_id,
                'product_name'      => $cashierDetail->product_name,
                'goods_id'          => $cashierDetail->goods_id,
                'goods_name'        => $cashierDetail->goods_name,
                'times'             => $cashierDetail->times,
                'unit_id'           => $cashierDetail->unit_id,
                'specs'             => $cashierDetail->specs,
            ]);
            // 更新顾客余额
            $customer->update([
                'balance' => $after
            ]);
        }

        // 创建[顾客物品表]
        if ($cashierDetail->goods_id) {
            $table_id = $this->createCustomerGoods($cashierDetail, $order, $reception);
        }

        // 项目欠款
        if ($cashierDetail->arrearage && $table_id) {
            $this->createArrearageRecord($cashierDetail, $table_id);
        }

        // 创建[顾客积分]
        $this->createCustomerIntegral($cashierDetail, $cashierDetail->product, $cashierDetail->goods, $customer);

        // 计算业绩
        $this->createSalesPerformance($cashierDetail, $reception->type);
    }

    /**
     * 更新项目成交状态
     * @param $detail
     * @param $reception
     */
    private function updateReceptionItems($detail, $reception)
    {
        $product = Product::query()->find($detail->product_id);
        // 统计成交
        if ($product->successful) {
            $reception->receptionItems()->where('item_id', $product->type_id)->update(['successful' => 1]);
        }
    }

    /**
     * 创建顾客项目明细表
     * @param $detail
     * @param $order
     * @param $reception
     * @return \Illuminate\Database\Eloquent\HigherOrderBuilderProxy|mixed
     */
    private function createCustomerProduct($detail, $order, $reception)
    {
        return CustomerProduct::query()->create([
            'cashier_id'        => $detail->cashier_id,
            'cashier_detail_id' => $detail->id,
            'customer_id'       => $detail->customer_id,
            'product_id'        => $detail->product_id,
            'product_name'      => $order->product_name,
            'package_id'        => $order->package_id,
            'package_name'      => $order->package_name,
            'status'            => ($detail->product->deduct == 0) ? 2 : 1,     // 项目状态(判断是否需要划扣)
            'expire_time'       => $detail->product->expiration > 0 ? Carbon::now()->addMonths($detail->product->expiration) : null,  // 根据使用期限计算过期时间
            'times'             => $order->times,                               // 项目次数
            'used'              => 0,                                           // 已用次数
            'leftover'          => ($detail->product->deduct == 0) ? 0 : $order->times, // 剩余次数
            'refund_times'      => 0,                                           // 已退次数
            'price'             => $order->price,                               // 项目原价
            'sales_price'       => $order->sales_price,                         // 执行价格
            'payable'           => $detail->payable,                            // (成交价)应收金额
            'income'            => $detail->income,                             // 实收金额(不包括余额支付)
            'deposit'           => $detail->deposit,                            // 余额支付
            'coupon'            => $detail->coupon,                             // 卡券支付
            'arrearage'         => $detail->arrearage,                          // 本单欠款金额
            'user_id'           => $detail->user_id,                            // 收银员(操作员)
            'consultant'        => $reception->consultant,                      // 现场咨询
            'ek_user'           => $reception->ek_user,                         // 二开人员
            'doctor'            => $reception->doctor,                          // 助诊医生
            'reception_type'    => $reception->type,                            // 接诊类型
            'medium_id'         => $reception->medium_id,                       // 媒介来源
            'department_id'     => $detail->department_id,                      // 结算科室
            'deduct_department' => $detail->product->deduct_department,         // 划扣科室
            'salesman'          => $detail->salesman
        ])->id;
    }

    /**
     * 顾客物品明细表
     * @param $detail
     * @param $order
     * @param $reception
     * @return \Illuminate\Database\Eloquent\HigherOrderBuilderProxy|mixed
     */
    private function createCustomerGoods($detail, $order, $reception)
    {
        $baseUnit    = GoodsUnit::basic()->where('goods_id', $detail->goods_id)->first();
        $currentUnit = GoodsUnit::query()->where('unit_id', $detail->unit_id)->where('goods_id', $detail->goods_id)->first();
        $unit_id     = $detail->unit_id;
        $number      = $detail->times;

        // 转换为最小单位
        if ($detail->unit_id != $baseUnit->unit_id) {
            $number  = bcmul($detail->times, $currentUnit->rate, 4);
            $unit_id = $baseUnit->unit_id;
        }

        return CustomerGoods::query()->create([
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
            'price'             => $order->price,
            'payable'           => $detail->payable,                            // (成交价)应收金额
            'income'            => $detail->income,                             // 实收金额(不包括余额支付)
            'deposit'           => $detail->deposit,                            // 余额支付
            'coupon'            => $detail->coupon,                             // 卡券支付
            'arrearage'         => $detail->arrearage,                          // 本单欠款金额
            'user_id'           => $detail->user_id,                            // 收银员(操作员)
            'consultant'        => $reception->consultant,                      // 现场咨询
            'ek_user'           => $reception->ek_user,                         // 二开人员
            'doctor'            => $reception->doctor,                          // 助诊医生
            'reception_type'    => $reception->type,                            // 接诊类型
            'medium_id'         => $reception->medium_id,                       // 媒介来源
            'department_id'     => $detail->department_id,                      // 结算科室
            'salesman'          => $detail->salesman
        ])->id;
    }


    /**
     * 写入欠款记录
     * @param $detail
     * @param $table_id (customer_product|customer_goods)
     */
    private function createArrearageRecord($detail, $table_id)
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
    private function createCustomerIntegral($detail, $product, $goods, $customer)
    {
        $rate         = parameter('cywebos_integral_rate');                                            // 积分比例
        $integral     = ($detail->income + $detail->deposit) * $rate;                                        // 计算当前项目积分
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
                'integral'    => 0,                      // 变动积分
                'after'       => $customer->integral,    // 现有积分
                'remark'      => $remark[$type] . '没有开启积分',
                'data'        => $detail
            ]);
        } else {
            Integral::query()->create([
                'customer_id' => $customer->id,
                'type'        => $type,
                'type_id'     => $detail->cashier_id,               // 业务单号
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
     * 创建销售业绩
     * @param $detail
     * @param $reception_type
     */
    private function createSalesPerformance($detail, $reception_type)
    {
        $product             = $detail->product;
        $goods               = $detail->goods;
        $customer            = $detail->customer;
        $cashier             = $detail->cashier;
        $sales_commission    = $detail->product_id ? $product->commission : $goods->commission;    // 项目|物品 是否提成
        $recharge_commission = Product::query()->find(1)->commission;;   // 预收费用 是否提成
        $sales_commission_amount    = $sales_commission ? $detail->income : 0;  // 实收提成
        $recharge_commission_amount = !$recharge_commission ? $detail->deposit : 0;  // 余款支付提成

        // 销售人员提成
        if (is_array($detail->salesman) && count($detail->salesman)) {
            foreach ($detail->salesman as $v) {
                $num1   = ($sales_commission_amount * $v['rate']) / 100;
                $num2   = ($recharge_commission_amount * $v['rate']) / 100;
                $remark = "实收金额:{$detail->income}计提金额:{$num1},余额支付:{$detail->deposit}计提金额{$num2},券额支付:{$detail->coupon}";
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
                    'coupon'         => $detail->coupon,
                    'rate'           => $v['rate'],
                    'remark'         => $remark
                ]);
            }
        }

        // 开发人员
        if ($customer->ascription) {
            $remark = "实收金额:{$detail->income},计提金额:{$sales_commission_amount},余额支付:{$detail->deposit}计提金额{$recharge_commission_amount},券额支付:{$detail->coupon}";
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
                'coupon'         => $detail->coupon,
                'rate'           => 100,
                'remark'         => $remark
            ]);
        }
    }

    /**
     * 收费完成后
     * @param $cashier
     */
    public function handleCashier($cashier, $customer)
    {
        // 更新{现场咨询单}状态
        $successful = false; // 默认未成交(拓客项目,小气泡之类的可设置为成交)
        $product_id = $cashier->cashierable->orders->pluck('product_id')->filter()->toArray();
        $goods_id   = $cashier->cashierable->orders->pluck('goods_id')->filter()->toArray();

        // 购买物品
        if (count($goods_id)) {
            $successful = true;
        }

        // 只购买项目
        if (!$successful && Product::query()->whereIn('id', $product_id)->where('successful', 1)->count() > 0) {
            $successful = true;
        }

        // 更新为成交
        if ($successful) {
            $cashier->cashierable()->update(['status' => 2]);
        }

        // 本次收费,总付款金额
        $income = $cashier->pay()->where('accounts_id', '<>', 1)->sum('income');

        // 本次(项目/物品)消费金额,排除预收费
        $payable = $cashier->details()->where(function ($query) {
            $query->where('product_id', '<>', 1)->orWhereNull('product_id');
        })->sum('payable');

        // 3、预收费用
//        $ysf = $cashier->details()->where(function ($query) {
//            $query->where('product_id', 1);
//        })->sum('income');

        // 4、余额支付，扣减账户余额
//        $balance = $cashier->pay()->where('accounts_id', 1)->sum('income');

        // 更新顾客(付款)信息
        $customer->update([
            'total_payment' => $customer->total_payment + $income,
            'amount'        => $customer->amount + $payable,
//            'balance'       => $customer->balance + $ysf - $balance,
            'arrearage'     => $customer->arrearage + $cashier->arrearage
        ]);
    }

    /**
     * 卡券变动明细
     * @param $cashierCoupon
     * @return array
     */
    public function couponDetailHistoryData($cashierCoupon): array
    {
        $couponDetail = $cashierCoupon->couponDetail;
        return [
            'coupon_id'        => $cashierCoupon->coupon_id,
            'coupon_detail_id' => $cashierCoupon->coupon_detail_id,
            'coupon_number'    => $cashierCoupon->coupon_number,
            'customer_id'      => $cashierCoupon->customer_id,
            'before'           => $couponDetail->balance,
            'amount'           => -1 * abs($cashierCoupon->income),
            'after'            => bcsub($couponDetail->balance, $cashierCoupon->income, 4),
            'remark'           => $cashierCoupon->remark
        ];
    }

    /**
     * 更新卡券信息
     * @param $cashierCoupon
     * @return array
     */
    public function couponDetailData($cashierCoupon): array
    {
        $couponDetail = $cashierCoupon->couponDetail;
        $updateData   = [
            'balance' => bcsub($couponDetail->balance, $cashierCoupon->income, 4),
            'status'  => 2, // 部分使用
        ];

        if ($updateData['balance'] == 0) {
            $updateData['status'] = 3; // 用完了
        }

        return $updateData;
    }
}
