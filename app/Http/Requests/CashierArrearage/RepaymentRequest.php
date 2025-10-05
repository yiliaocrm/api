<?php

namespace App\Http\Requests\CashierArrearage;

use App\Models\Product;
use App\Models\Integral;
use App\Models\CustomerGoods;
use App\Models\CustomerProduct;
use App\Models\SalesPerformance;
use App\Models\CashierArrearage;
use Illuminate\Foundation\Http\FormRequest;

class RepaymentRequest extends FormRequest
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
            'id'                => [
                'required',
                'exists:cashier_arrearage,id',
                function ($attribute, $value, $fail) {
                    $pay       = collect($this->input('pay'));
                    $arrearage = CashierArrearage::query()->find($value);

                    if ($arrearage && $this->input('form.income') > $arrearage->leftover) {
                        $fail('[还款金额]不能大于项目[尚欠金额]');
                    }

                    // 余额支付 大于 账户实际余额
                    if ($pay->where('accounts_id', 1)->sum('income') > $arrearage->customer->balance) {
                        return $fail('账户余额不够支付');
                    }

                }
            ],
            'form.income'       => 'required|numeric',
            'pay'               => 'required|array',
            'pay.*.accounts_id' => 'required|exists:accounts,id',
            'pay.*.income'      => 'required|numeric|gt:0',
        ];
    }

    public function messages(): array
    {
        return [
            'cashier_arrearage_id.required' => '没有找到欠款记录!',
            'pay.required'                  => '支付方式不能为空!',
            'pay.*.income.gt'               => '还款金额必须大于0'
        ];
    }

    /**
     * 还款记录
     * @param $arrearage
     * @param $cashier_id
     * @return array
     */
    public function detailsData($arrearage, $cashier_id): array
    {
        return [
            'cashier_arrearage_id' => $this->input('id'),
            'customer_id'          => $arrearage->customer_id,
            'cashier_id'           => $cashier_id,
            'package_id'           => $arrearage->package_id,
            'package_name'         => $arrearage->package_name,
            'product_id'           => $arrearage->product_id,
            'product_name'         => $arrearage->product_name,
            'goods_id'             => $arrearage->goods_id,
            'goods_name'           => $arrearage->goods_name,
            'times'                => $arrearage->times,
            'unit_id'              => $arrearage->unit_id,
            'specs'                => $arrearage->specs,
            'income'               => $this->input('form.income'),
            'remark'               => $this->input('form.remark'),
            'salesman'             => $this->input('form.salesman'),
            'department_id'        => $this->input('form.department_id'),
            'user_id'              => user()->id
        ];
    }

    /**
     * 收费通知单
     * @param $arrearage
     * @return array
     */
    public function cashierData($arrearage): array
    {
        return [
            'customer_id' => $arrearage->customer_id,
            'status'      => 1, // 未收款
            'payable'     => $this->input('form.income'),
            'income'      => 0,
            'deposit'     => 0,
            'arrearage'   => 0,
            'user_id'     => user()->id,
            'operator'    => user()->id,
            'detail'      => []
        ];
    }

    /**
     * 付款记录
     * @param $arrearage
     * @return array
     */
    public function payData($arrearage): array
    {
        $data = [];

        foreach ($this->input('pay') as $p) {
            $data[] = [
                'customer_id' => $arrearage->customer_id,
                'accounts_id' => $p['accounts_id'],
                'income'      => $p['income'],
                'remark'      => $p['remark'] ?? null,
                'user_id'     => user()->id
            ];
        }

        return $data;
    }

    /**
     * 写入营收明细
     * @param $cashier
     * @param $arrearage
     * @return array
     */
    public function cashierDetailData($cashier, $arrearage): array
    {
        return [
            'customer_id'      => $arrearage->customer_id,
            'cashierable_type' => $cashier->cashierable_type,
            'table_name'       => $arrearage->product_id ? 'customer_product' : 'customer_goods',
            'table_id'         => $arrearage->table_id,
            'package_id'       => $arrearage->package_id,
            'package_name'     => $arrearage->package_name,
            'product_id'       => $arrearage->product_id,
            'product_name'     => $arrearage->product_name,
            'goods_id'         => $arrearage->goods_id,
            'goods_name'       => $arrearage->goods_name,
            'times'            => $arrearage->times,
            'unit_id'          => $arrearage->unit_id,
            'specs'            => $arrearage->specs,
            'payable'          => $this->input('form.income'),
            'income'           => $this->input('form.income'),
            'arrearage'        => 0,
            'deposit'          => 0,
            'department_id'    => $this->input('form.department_id'),
            'salesman'         => $this->input('form.salesman'),
            'user_id'          => user()->id,
        ];
    }

    /**
     * 更新还款后,各种逻辑
     * @param $cashierDetail
     */
    public function cashierAfter($cashierDetail)
    {
        // 接诊类型
        $reception_type = null;

        // 更新 {顾客项目表}欠款金额
        if ($cashierDetail->table_name == 'customer_product') {
            $customerProduct = CustomerProduct::query()->find($cashierDetail->table_id);
            $customerProduct->update([
                'arrearage' => $customerProduct->arrearage - $cashierDetail->payable,
                'income'    => $customerProduct->income + $cashierDetail->payable
            ]);
            $reception_type = $customerProduct->reception_type;

            // 配台人员技提
            $this->createServicePerformance($cashierDetail, $customerProduct);
        }

        // 更新 {顾客物品表}欠款金额
        if ($cashierDetail->table_name == 'customer_goods') {
            $customerGoods = CustomerGoods::query()->find($cashierDetail->table_id);
            $customerGoods->update([
                'arrearage' => $customerGoods->arrearage - $cashierDetail->payable
            ]);
            $reception_type = $customerGoods->reception_type;
        }

        // 创建客户项目积分
        $this->createCustomerIntegrals($cashierDetail, $cashierDetail->product, $cashierDetail->goods, $cashierDetail->customer);

        // 写入相关人员业绩
        $this->createSalesPerformance($cashierDetail, $reception_type);
    }

    /**
     * 配台人员,服务计提
     * @param $cashierDetail
     * @param $customerProduct
     * @return false
     */
    private function createServicePerformance($cashierDetail, $customerProduct)
    {
        // 欠款的划扣记录
        $treatments = $customerProduct->treatments()->where('arrearage', '<>', 0)->get();
        // 收费记录
        $cashier = $cashierDetail->cashier;
        // 实收金额+存款支付
        $amount = $cashierDetail->income + $cashierDetail->deposit;

        if ($amount <= 0) {
            return false;
        }

        foreach ($treatments as $treatment) {
            $_amount = ($amount > $treatment->arrearage) ? $treatment->arrearage : $amount;

            // 配台人员
            if (!empty($treatment->participants)) {
                foreach ($treatment->participants as $participant) {
                    SalesPerformance::query()->create([
                        'cashier_id'     => $cashierDetail->cashier_id,
                        'customer_id'    => $cashierDetail->customer_id,
                        'position'       => 3,
                        'table_name'     => $cashier->cashierable_type,
                        'table_id'       => $cashier->cashierable_id,
                        'user_id'        => $participant['user_id'],
                        'reception_type' => $customerProduct->reception_type,
                        'package_id'     => $cashierDetail->package_id,
                        'package_name'   => $cashierDetail->package_name,
                        'product_id'     => $cashierDetail->product_id,
                        'product_name'   => $cashierDetail->product_name,
                        'goods_id'       => null,
                        'goods_name'     => null,
                        'payable'        => $cashierDetail->payable,
                        'income'         => $cashierDetail->income,
                        'arrearage'      => $cashierDetail->arrearage,
                        'deposit'        => $cashierDetail->deposit,
                        'amount'         => $_amount,
                        'rate'           => 100,
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
     * 顾客还款,创建积分明细
     * @ 顾客积分,没有更新到顾客表
     * @param $cashierDetail
     * @param $product
     * @param $goods
     * @param $customer
     */
    private function createCustomerIntegrals($cashierDetail, $product, $goods, $customer)
    {
        $rate         = parameter('cywebos_integral_rate'); // 积分比例
        $integral     = ($cashierDetail->income + $cashierDetail->deposit) * $rate; // 计算当前项目积分
        $integralable = $cashierDetail->product_id ? $product->integral : $goods->integral; // 项目|物品 是否开启积分
        $type         = $cashierDetail->goods_id ? 3 : 2; // 积分类型:1、充值 2、项目 3、物品

        $remark = [
            2 => "项目还款：{$cashierDetail->product_name}\r\n实收金额:{$cashierDetail->income}\r\n余额支付：{$cashierDetail->deposit}",
            3 => "购买商品：{$cashierDetail->goods_name}\r\n实收金额:{$cashierDetail->income}\r\n余额支付：{$cashierDetail->deposit}"
        ];

        // 没有开启积分
        if (!$integralable || !parameter('cywebos_integral_enable')) {
            Integral::query()->create([
                'customer_id' => $customer->id,
                'type'        => $type,
                'type_id'     => $cashierDetail->cashier_id,    // 业务单号
                'before'      => $customer->integral,    // 原有积分
                'integral'    => 0,                      // 变动积分
                'after'       => $customer->integral,    // 现有积分
                'remark'      => $remark[$type] . '没有开启积分',
                'data'        => $cashierDetail
            ]);
        } else {
            Integral::query()->create([
                'customer_id' => $customer->id,
                'type'        => $type,
                'type_id'     => $cashierDetail->cashier_id,               // 业务单号
                'before'      => $customer->integral,               // 原有积分
                'integral'    => $integral,                         // 变动积分
                'after'       => $customer->integral + $integral,   // 现有积分
                'remark'      => $remark[$type],
                'data'        => $cashierDetail
            ]);
        }
    }

    /**
     * 业绩提成写入
     * @param $cashierDetail
     * @param $reception_type
     */
    private function createSalesPerformance($cashierDetail, $reception_type)
    {
        $product             = $cashierDetail->product;
        $goods               = $cashierDetail->goods;
        $customer            = $cashierDetail->customer;
        $cashier             = $cashierDetail->cashier;
        $sales_commission    = $cashierDetail->product_id ? $product->commission : $goods->commission;    // 项目|物品 是否提成
        $recharge_commission = Product::query()->find(1)->commission;;   // 预收费用 是否提成
        $sales_commission_amount    = $sales_commission ? $cashierDetail->income : 0;  // 实收提成
        $recharge_commission_amount = !$recharge_commission ? $cashierDetail->deposit : 0;  // 余款支付提成

        // 销售人员提成
        if (is_array($cashierDetail->salesman) && count($cashierDetail->salesman)) {
            foreach ($cashierDetail->salesman as $v) {
                $num1   = ($sales_commission_amount * $v['rate']) / 100;
                $num2   = ($recharge_commission_amount * $v['rate']) / 100;
                $remark = "实收金额:{$cashierDetail->income}计提金额:{$num1},余额支付:{$cashierDetail->deposit}计提金额{$num2},券额支付:{$cashierDetail->coupon}";
                SalesPerformance::query()->create([
                    'cashier_id'     => $cashierDetail->cashier_id,
                    'customer_id'    => $cashierDetail->customer_id,
                    'position'       => 1,
                    'table_name'     => $cashier->cashierable_type,
                    'table_id'       => $cashier->cashierable_id,
                    'user_id'        => $v['user_id'],
                    'reception_type' => $reception_type,
                    'package_id'     => $cashierDetail->package_id,
                    'package_name'   => $cashierDetail->package_name,
                    'product_id'     => $cashierDetail->product_id,
                    'product_name'   => $cashierDetail->product_name,
                    'goods_id'       => $cashierDetail->goods_id,
                    'goods_name'     => $cashierDetail->goods_name,
                    'payable'        => $cashierDetail->payable,
                    'income'         => $cashierDetail->income,
                    'arrearage'      => $cashierDetail->arrearage,
                    'deposit'        => $cashierDetail->deposit,
                    'amount'         => (($sales_commission_amount + $recharge_commission_amount) * $v['rate']) / 100,
                    'coupon'         => $cashierDetail->coupon,
                    'rate'           => $v['rate'],
                    'remark'         => $remark
                ]);
            }
        }

        // 开发人员
        if ($customer->ascription) {
            $remark = "实收金额:{$cashierDetail->income},计提金额:{$sales_commission_amount},余额支付:{$cashierDetail->deposit}计提金额{$recharge_commission_amount},券额支付:{$cashierDetail->coupon}";
            SalesPerformance::query()->create([
                'cashier_id'     => $cashierDetail->cashier_id,
                'customer_id'    => $cashierDetail->customer_id,
                'position'       => 2,
                'table_name'     => $cashier->cashierable_type,
                'table_id'       => $cashier->cashierable_id,
                'user_id'        => $customer->ascription,
                'reception_type' => $reception_type,
                'package_id'     => $cashierDetail->package_id,
                'package_name'   => $cashierDetail->package_name,
                'product_id'     => $cashierDetail->product_id,
                'product_name'   => $cashierDetail->product_name,
                'goods_id'       => $cashierDetail->goods_id,
                'goods_name'     => $cashierDetail->goods_name,
                'payable'        => $cashierDetail->payable,
                'income'         => $cashierDetail->income,
                'arrearage'      => $cashierDetail->arrearage,
                'deposit'        => $cashierDetail->deposit,
                'amount'         => $sales_commission_amount + $recharge_commission_amount,
                'coupon'         => $cashierDetail->coupon,
                'rate'           => 100,
                'remark'         => $remark
            ]);
        }

    }

    /**
     * 更新欠款单
     * @param $arrearage
     * @return array
     */
    public function updateData($arrearage): array
    {
        $leftover = $arrearage->leftover - $this->input('form.income');
        return [
            'status'              => ($leftover > 0) ? 1 : 2,
            'last_repayment_time' => now(),
            'amount'              => $arrearage->amount + $this->input('form.income'),
            'leftover'            => $leftover,
        ];
    }

    /**
     * 更新顾客信息
     * @param $cashier
     * @return array
     */
    public function customerData($cashier): array
    {
        // 余额支付，扣减账户余额
        $balance = $cashier->pay()->where('accounts_id', 1)->sum('income');
        return [
            'total_payment' => $cashier->customer->total_payment + $cashier->income,
            'arrearage'     => $cashier->customer->arrearage - $cashier->income,
            'balance'       => $cashier->customer->balance - $balance,
        ];
    }
}
