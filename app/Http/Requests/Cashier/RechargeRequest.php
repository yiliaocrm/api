<?php

namespace App\Http\Requests\Cashier;

use App\Models\Product;
use App\Models\Integral;
use App\Models\Customer;
use App\Models\Recharge;
use App\Models\CustomerProduct;
use App\Models\SalesPerformance;
use App\Models\CustomerDepositDetail;
use Illuminate\Foundation\Http\FormRequest;

class RechargeRequest extends FormRequest
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
            'customer_id'        => [
                'required',
                function ($attribute, $customer_id, $fail) {
                    $customer = Customer::query()->find($customer_id);
                    if (!$customer) {
                        return $fail('没有找到顾客信息!');
                    }
                    if ($customer->arrearage) {
                        return $fail('顾客有欠款,请还款后充值!');
                    }
                }
            ],
            'balance'            => 'required|numeric|min:1',
            'medium_id'          => 'required|exists:medium,id',
            'department_id'      => 'required|exists:department,id',
            'type'               => 'required|numeric',
            'salesman'           => 'nullable|array',
            'salesman.*.user_id' => 'required|exists:users,id',
            'salesman.*.rate'    => 'required|numeric',
            'pay'                => 'required|array',
            'pay.*.accounts_id'  => 'required|distinct|not_in:1|exists:accounts,id',
            'pay.*.income'       => 'required|min:1'
        ];
    }

    public function messages(): array
    {
        return [
            'customer_id.required'       => '缺少顾客id参数!',
            'customer_id.exists'         => '没有找到顾客信息',
            'balance.required'           => '充值金额不能为空',
            'balance.min'                => '充值金额不能小于1',
            'medium.required'            => '媒介来源不能为空!',
            'medium.exists'              => '没有找到媒介来源信息',
            'department_id.required'     => '结算科室不能为空',
            'department_id.exists'       => '没有找到科室信息',
            'type.required'              => '分诊类型不能为空!',
            'pay.*.accounts_id.required' => '收款账户不能为空!',
            'pay.*.accounts_id.not_in'   => '结算账户错误',
            'pay.*.accounts_id.exists'   => '没有找到结算账户',
            'pay.*.accounts_id.distinct' => '收款账户不能重复!',
        ];
    }

    /**
     * 充值信息
     * @return array
     */
    public function fillData(): array
    {
        return [
            'customer_id'   => $this->input('customer_id'),
            'balance'       => $this->input('balance'),
            'department_id' => $this->input('department_id'),
            'medium_id'     => $this->input('medium_id'),
            'type'          => $this->input('type'),
            'salesman'      => $this->input('salesman'),
            'remark'        => $this->input('remark'),
            'user_id'       => user()->id,
        ];
    }

    /**
     * 收费通知
     * @return array
     */
    public function cashierData(): array
    {
        return [
            'customer_id' => $this->input('customer_id'),
            'status'      => 2, // 已收款
            'payable'     => $this->input('balance'),
            'income'      => $this->input('balance'),
            'deposit'     => 0,
            'arrearage'   => 0,
            'user_id'     => user()->id,
            'operator'    => user()->id,
            'detail'      => $this->fillData()
        ];
    }

    /**
     * 创建营收明细
     * @param $cashier
     * @param $recharge
     * @return array
     */
    public function cashierDetailData($cashier, $recharge): array
    {
        return [
            'customer_id'      => $this->input('customer_id'),
            'cashierable_type' => $cashier->cashierable_type,
            'table_name'       => 'recharge',
            'table_id'         => $recharge->id,
            'package_id'       => null,
            'package_name'     => null,
            'product_id'       => 1,    // 系统中是预收费
            'product_name'     => '收费充值',
            'goods_id'         => null,
            'goods_name'       => null,
            'times'            => 1,
            'unit_id'          => null,
            'specs'            => null,
            'payable'          => $this->input('balance'),
            'income'           => $this->input('balance'),
            'arrearage'        => 0,
            'deposit'          => 0,
            'department_id'    => $this->input('department_id'),
            'salesman'         => $this->input('salesman'),
            'user_id'          => user()->id,
        ];
    }

    public function payData(): array
    {
        $data = [];

        foreach ($this->input('pay') as $p) {
            $data[] = [
                'customer_id' => $this->input('customer_id'),
                'accounts_id' => $p['accounts_id'],
                'income'      => $p['income'],
                'remark'      => $p['remark'] ?? null,
                'user_id'     => user()->id
            ];
        }

        return $data;
    }

    public function handleCashierDetail($cashier, $detail)
    {
        $recharge = Recharge::query()->find($detail->table_id);
        $product  = Product::query()->find(1);
        $customer = $cashier->customer;

        // 项目明细
        CustomerProduct::query()->create([
            'cashier_id'        => $detail->cashier_id,
            'cashier_detail_id' => $detail->id,
            'customer_id'       => $detail->customer_id,
            'product_id'        => 1,
            'product_name'      => '收费充值',
            'package_id'        => null,
            'package_name'      => null,
            'status'            => 2,                                           // 项目状态(判断是否需要划扣)
            'expire_time'       => null,                                        // product表现在还没过期选项
            'times'             => 1,                                           // 项目次数
            'used'              => 0,                                           // 已用次数
            'leftover'          => 0,                                           // 剩余次数
            'refund_times'      => 0,                                           // 已退次数
            'price'             => 0,                                           // 项目原价
            'sales_price'       => 0,                                           // 执行价格
            'payable'           => $detail->payable,                            // (成交价)应收金额
            'income'            => $detail->income,                             // 实收金额(不包括余额支付)
            'deposit'           => $detail->deposit,                            // 余额支付
            'coupon'            => 0,                                           // 卡券支付
            'arrearage'         => 0,                                           // 本单欠款金额
            'user_id'           => $detail->user_id,                            // 收银员(操作员)
            'consultant'        => null,                                        //
            'ek_user'           => null,
            'doctor'            => null,
            'reception_type'    => $recharge->type,
            'medium_id'         => $recharge->medium_id,
            'department_id'     => $detail->department_id,                      // 结算科室
            'deduct_department' => $product->deduct_department,                 // 划扣科室
            'salesman'          => $detail->salesman
        ]);

        // 创建[顾客积分]
        $this->createCustomerIntegral($detail, $detail->product, $detail->customer);

        // 写入业绩
        $this->createSalesPerformance($cashier, $detail, $detail->product, $detail->customer, $recharge->type);

        // 创建[预收账款]变动明细表
        $this->createCustomerDepositDetail($customer, $detail);

        // 更新顾客信息
        $customer->update([
            'total_payment' => $customer->total_payment + $cashier->income,
            'balance'       => $customer->balance + $cashier->income,
        ]);
    }

    /**
     * 写入顾客积分
     * @param $detail
     * @param $product
     * @param $customer
     */
    private function createCustomerIntegral($detail, $product, $customer)
    {
        $rate         = parameter('cywebos_integral_rate');       // 积分比例
        $integral     = ($detail->income + $detail->deposit) * $rate;   // 计算当前项目积分
        $integralable = $product->integral; // 是否开启积分

        $insertData = [
            'customer_id' => $customer->id,
            'type'        => 1,                                 // 充值赠送积分
            'type_id'     => $detail->cashier_id,               // 业务单号
            'before'      => $customer->integral,               // 原有积分
            'integral'    => $integral,                         // 变动积分
            'after'       => $customer->integral + $integral,   // 现有积分
            'remark'      => "收费充值：实收金额:{$detail->income}",
            'data'        => $detail
        ];

        // 充值 没有开启积分 或 系统关闭积分功能
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
     * 写入业绩
     * @param $cashier
     * @param $detail
     * @param $product
     * @param $customer
     * @param $reception_type
     */
    private function createSalesPerformance($cashier, $detail, $product, $customer, $reception_type)
    {
        // 销售人员提成
        if (is_array($detail->salesman) && count($detail->salesman)) {
            foreach ($detail->salesman as $v) {
                // 提成金额
                $amount = $product->commission ? ($detail->income * $v['rate']) / 100 : 0;
                SalesPerformance::query()->create([
                    'cashier_id'     => $detail->cashier_id,
                    'customer_id'    => $detail->customer_id,
                    'position'       => 1,
                    'table_name'     => $cashier->cashierable_type,
                    'table_id'       => $cashier->cashierable_id,
                    'user_id'        => $v['user_id'],
                    'reception_type' => $reception_type,
                    'package_id'     => null,
                    'package_name'   => null,
                    'product_id'     => 1,
                    'product_name'   => $detail->product_name,
                    'goods_id'       => null,
                    'goods_name'     => null,
                    'payable'        => $detail->payable,
                    'income'         => $detail->income,
                    'arrearage'      => $detail->arrearage,
                    'deposit'        => $detail->deposit,
                    'amount'         => $amount,
                    'rate'           => $v['rate'],
                    'remark'         => "实收金额:{$detail->income}计提金额:{$amount}"
                ]);
            }
        }

        // 开发人员
        if ($customer->ascription) {
            // 提成金额
            $amount = $product->commission ? $detail->income : 0;
            SalesPerformance::query()->create([
                'cashier_id'     => $detail->cashier_id,
                'customer_id'    => $detail->customer_id,
                'position'       => 2,
                'table_name'     => $cashier->cashierable_type,
                'table_id'       => $cashier->cashierable_id,
                'user_id'        => $customer->ascription,
                'reception_type' => $reception_type,
                'package_id'     => null,
                'package_name'   => null,
                'product_id'     => $detail->product_id,
                'product_name'   => $detail->product_name,
                'goods_id'       => null,
                'goods_name'     => null,
                'payable'        => $detail->payable,
                'income'         => $detail->income,
                'arrearage'      => $detail->arrearage,
                'deposit'        => $detail->deposit,
                'amount'         => $amount,
                'rate'           => 100,
                'remark'         => "实收金额:{$detail->income}计提金额:{$amount}"
            ]);
        }
    }

    /**
     * 预收账款变动明细
     * @param $customer
     * @param $cashierDetail
     */
    public function createCustomerDepositDetail($customer, $cashierDetail)
    {
        CustomerDepositDetail::query()->create([
            'customer_id'       => $customer->id,
            'cashier_id'        => $cashierDetail->cashier_id,
            'cashier_detail_id' => $cashierDetail->id,
            'before'            => $customer->balance,
            'balance'           => $cashierDetail->income,
            'after'             => bcadd($customer->balance, $cashierDetail->income, 4),
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
    }
}
