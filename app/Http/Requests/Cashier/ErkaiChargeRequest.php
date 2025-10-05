<?php

namespace App\Http\Requests\Cashier;

use App\Models\Cashier;
use App\Models\Product;
use App\Models\Integral;
use App\Models\GoodsUnit;
use App\Models\ErkaiDetail;
use App\Models\CustomerGoods;
use App\Models\CustomerProduct;
use App\Models\CashierArrearage;
use App\Models\SalesPerformance;
use App\Models\CustomerDepositDetail;
use Illuminate\Foundation\Http\FormRequest;

class ErkaiChargeRequest extends FormRequest
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
            'id'                  => [
                'required',
                function ($attribute, $cashier_id, $fail) {
                    $pay     = collect($this->input('pay'));
                    $detail  = collect($this->input('detail'));
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

                    if ($pay->sum('income') > $detail->sum('payable')) {
                        return $fail('【合计付款】不能大于【合计应收】');
                    }

                    // 余额支付 大于 账户实际余额
                    if ($pay->where('accounts_id', 1)->sum('income') > $cashier->customer->balance) {
                        return $fail('账户余额不够支付');
                    }

                }
            ],
            'detail'              => 'required|array',
            'detail.*.product_id' => 'nullable|exists:product,id,disabled,0',
            'pay'                 => 'nullable|array',
            'pay.*.accounts_id'   => 'required|distinct|exists:accounts,id',
            'pay.*.income'        => 'required|numeric|gt:0',
        ];
    }

    public function messages(): array
    {
        return [
            'id.required'                => '缺少id参数',
            'pay.*.accounts_id.distinct' => '收款账户不能重复!',
            'pay.*.income.required'      => '[收款金额]不能为空!',
            'pay.*.income.gt'            => '[收款金额]不能为0',
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
     * 支付信息
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
     * 营收明细
     * @param $cashier
     * @return array
     */
    public function CashierDetailData($cashier): array
    {
        $data     = [];
        $detail   = collect($cashier->detail)->sortBy('product_id');                        // 按项目排序
        $paycount = collect($cashier->pay)->where('accounts_id', '<>', 1)->sum('income');   // 实收金额(不包括余额支付)
        $balance  = collect($cashier->pay)->where('accounts_id', 1)->sum('income');         // 余额支付费用
        $amount   = $paycount + $balance;                                                   // 合计支付费用

        // 费用摊到各个项目上
        foreach ($detail as $k => $v) {
            $income    = 0; // 本单实收金额
            $deposit   = 0; // 本单余额支付
            $arrearage = 0; // 本单欠款金额

            if ($amount) {
                if ($amount >= $v['payable']) {
                    // 预收费用,使用{实收金额}结算
                    if ($v['product_id'] == 1) {
                        $income  = $v['payable'];
                        $deposit = 0;
                    } // 实收 && 实收 > 项目价格
                    elseif ($paycount && $paycount >= $v['payable']) {
                        $income  = $v['payable'];
                        $deposit = 0;
                    } // 实收 && 实收 < 项目价格
                    elseif ($paycount && $paycount < $v['payable']) {
                        $income  = $paycount;
                        $deposit = $v['payable'] - $paycount;
                    } else {
                        $income  = 0;
                        $deposit = $v['payable'];
                    }
                } else {
                    $income  = $paycount ? $paycount : 0;
                    $deposit = $balance ? $balance : 0;
                }
                $arrearage = $amount > $v['payable'] ? 0 : $v['payable'] - $amount;
            } else {
                $income    = 0;
                $deposit   = 0;
                $arrearage = $v['payable'];
            }

            // 扣减
            $paycount -= $income;
            $balance  -= $deposit;
            $amount   -= ($income + $deposit);

            $data[] = [
                'customer_id'      => $cashier->customer_id,
                'cashierable_type' => $cashier->cashierable_type,
                'table_name'       => 'erkai_detail',
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
                'income'           => $income,
                'arrearage'        => $arrearage,
                'deposit'          => $deposit,
                'department_id'    => $v['department_id'],
                'salesman'         => $v['salesman'],
                'user_id'          => user()->id,
            ];
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
        $arrearage = $cashier->payable - $income - $deposit;
        $detail    = $cashier->cashierable->details;

        return [
            'status'    => 2,
            'income'    => $income,
            'deposit'   => $deposit,
            'arrearage' => $arrearage,
            'operator'  => user()->id,
            'detail'    => $detail
        ];
    }

    /**
     * 收费项目明细处理
     * @param $cashierDetail
     * @param $customer
     */
    public function handleCashierDetail($cashierDetail, $customer)
    {
        // 更新二开detail表
        $order = ErkaiDetail::query()->find($cashierDetail->table_id);
        $erkai = $order->erkai;

        $table_id = null; // customer_product_id | customer_goods_id

        $order->update([
            'status' => 3, // 成交
            'amount' => $cashierDetail->income + $cashierDetail->deposit
        ]);

        // 创建[项目顾客明细表]
        if ($cashierDetail->product_id) {
            $table_id = $this->createCustomerProduct($cashierDetail, $order, $erkai);
        }

        // 创建[顾客物品表]
        if ($cashierDetail->goods_id) {
            $table_id = $this->createCustomerGoods($cashierDetail, $order, $erkai);
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

        // 欠款记录
        if ($cashierDetail->arrearage && $table_id) {
            $this->createArrearageRecord($cashierDetail, $table_id);
        }

        // 创建[顾客积分]
        $this->createCustomerIntegral($cashierDetail, $cashierDetail->product, $cashierDetail->goods, $customer);

        // 计算业绩
        $this->createSalesPerformance($cashierDetail, $erkai->type);
    }

    /**
     * 创建顾客项目明细表
     * @param $detail
     * @param $order
     * @param $erkai
     * @return \Illuminate\Database\Eloquent\HigherOrderBuilderProxy|mixed
     */
    private function createCustomerProduct($detail, $order, $erkai)
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
            'expire_time'       => null,                                        // product表现在还没过期选项
            'times'             => $order->times,                               // 项目次数
            'used'              => 0,                                           // 已用次数
            'leftover'          => ($detail->product->deduct == 0) ? 0 : $order->times, // 剩余次数
            'refund_times'      => 0,                                           // 已退次数
            'price'             => $order->price,                               // 项目原价
            'sales_price'       => $order->sales_price,                         // 执行价格
            'payable'           => $detail->payable,                            // (成交价)应收金额
            'income'            => $detail->income,                             // 实收金额(不包括余额支付)
            'deposit'           => $detail->deposit,                            // 余额支付
            'coupon'            => 0,                                           // 卡券支付
            'arrearage'         => $detail->arrearage,                          // 本单欠款金额
            'user_id'           => $detail->user_id,                            // 收银员(操作员)
            'consultant'        => null,                                        // 现场咨询
            'ek_user'           => $erkai->user_id,                             // 二开人员
            'doctor'            => null,                                        // 助诊医生
            'reception_type'    => $erkai->type,                                // 接诊类型
            'medium_id'         => $erkai->medium_id,                           // 媒介来源
            'department_id'     => $detail->department_id,                      // 结算科室
            'deduct_department' => $detail->product->deduct_department,         // 划扣科室
            'salesman'          => $detail->salesman
        ])->id;
    }

    /**
     * 顾客物品明细表
     * @param $detail
     * @param $order
     * @param $erkai
     * @return \Illuminate\Database\Eloquent\HigherOrderBuilderProxy|mixed
     */
    private function createCustomerGoods($detail, $order, $erkai)
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
            'coupon'            => 0,                                           // 卡券支付
            'arrearage'         => $detail->arrearage,                          // 本单欠款金额
            'user_id'           => $detail->user_id,                            // 收银员(操作员)
            'consultant'        => null,                                        // 现场咨询
            'ek_user'           => $erkai->user_id,                             // 二开人员
            'doctor'            => null,                                        // 助诊医生
            'reception_type'    => $erkai->type,                                // 接诊类型
            'medium_id'         => $erkai->medium_id,                           // 媒介来源
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
    private function createCustomerIntegral($detail, $product, $goods, $customer): void
    {
        // 欠款不记录积分
        if ($detail->arrearage) {
            return;
        }

        $rate         = parameter('cywebos_integral_rate');                       // 积分比例
        $integral     = ($detail->income + $detail->deposit) * $rate;                   // 计算当前项目积分
        $integralable = $detail->product_id ? $product->integral : $goods->integral;    // 项目|物品 是否开启积分
        $type         = $detail->product_id ? 2 : 3;                                    // 积分类型:1、充值 2、项目 3、物品

        $remark = [
            2 => "消费项目：{$detail->product_name}\r\n实收金额:{$detail->income}\r\n余额支付：{$detail->deposit}",
            3 => "购买商品：{$detail->goods_name}\r\n实收金额:{$detail->income}\r\n余额支付：{$detail->deposit}",
        ];

        $insertData = [
            'customer_id' => $customer->id,
            'type'        => $type,
            'type_id'     => $detail->cashier_id,               // 业务单号
            'before'      => $customer->integral,               // 原有积分
            'integral'    => $integral,                         // 变动积分
            'after'       => $customer->integral + $integral,   // 现有积分
            'remark'      => $remark[$type],
            'data'        => $detail
        ];

        // 项目|物品 没有开启积分 或 系统关闭积分功能
        if (!$integralable || !parameter('cywebos_integral_enable')) {
            $insertData['integral'] = 0;
        }

        // 写入积分
        Integral::query()->create(
            $insertData
        );

        // 积分变动
        if ($insertData['integral']) {
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
        $product                    = $detail->product;
        $goods                      = $detail->goods;
        $customer                   = $detail->customer;
        $cashier                    = $detail->cashier;
        $sales_commission           = $detail->product_id ? $product->commission : $goods->commission;    // 项目|物品 是否提成
        $recharge_commission        = Product::query()->find(1)->commission;   // 预收费用 是否提成
        $sales_commission_amount    = $sales_commission ? $detail->income : 0;  // 实收提成
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


    public function handleCashier($cashier, $customer)
    {
        // 收费后,更新二开表信息
        $erkaiUpdate = [
            'status'    => 2,
            'payable'   => $cashier->payable,
            'income'    => $cashier->income,
            'deposit'   => $cashier->deposit,
            'arrearage' => $cashier->arrearage
        ];

        // 更新二开表
        $cashier->cashierable()->update($erkaiUpdate);

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
}
