<?php

namespace App\Http\Requests\Coupon;

use App\Models\Coupon;
use App\Models\Customer;
use Illuminate\Foundation\Http\FormRequest;

class IssueRequest extends FormRequest
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
            'form'              => 'required|array',
            'form.coupon_id'    => [
                'required',
                function ($attribute, $coupon_id, $fail) {

                    $customer_id = $this->input('form.customer_id');
                    $coupon      = Coupon::query()->find($coupon_id);
                    $customer    = Customer::query()->find($customer_id);
                    $count       = $coupon->details()->where('customer_id', $customer_id)->count(); // 已领券数量
                    $pay         = collect($this->input('pay'));

                    if (!$coupon) {
                        return $fail('没有找到卡券信息');
                    }
                    if ($coupon->status == 2) {
                        return $fail('活动已下架!');
                    }
                    if ($coupon->status == 3) {
                        return $fail('活动已过期!');
                    }
                    if ($coupon->issue_count >= $coupon->total) {
                        return $fail('卡券已领完了!');
                    }
                    if ($coupon->quota && $count > $coupon->quota) {
                        return $fail("活动限制每人领取{$coupon->quota}张");
                    }
                    if ($coupon->integrals && $customer->integral < $coupon->integrals) {
                        return $fail('积分不够,无法换券!');
                    }
                    if ($coupon->sales_price && $pay->sum('income') != $coupon->sales_price) {
                        return $fail('[付款金额]不等于[卡券售价]!');
                    }
                    // 余额支付 大于 账户实际余额
                    if ($pay->where('accounts_id', 1)->sum('income') > $customer->balance) {
                        return $fail('账户余额不够支付');
                    }
                }
            ],
            'form.customer_id'  => 'required|exists:customer,id',
            'form.number'       => 'required|unique:coupon_details,number',
            'form.sales_price'  => 'required',
            'form.integrals'    => 'required',
            'pay'               => 'nullable|array',
            'pay.*.accounts_id' => 'required|distinct|exists:accounts,id',
            'pay.*.income'      => 'required|numeric|gt:0',
        ];
    }

    public function messages(): array
    {
        return [
            'form.coupon_id.required'    => 'coupon_id不能为空!',
            'form.customer_id.required'  => '[顾客信息]不能为空!',
            'form.number.unique'         => '[卡券编号]已存在!',
            'pay.*.accounts_id.distinct' => '收款账户不能重复!',
            'pay.*.income.required'      => '[收款金额]不能为空!',
            'pay.*.income.gt'            => '[收款金额]不能为0',
        ];
    }

    /**
     * 发券信息
     * @param $coupon
     * @return array
     */
    public function formData($coupon): array
    {
        return [
            'status'         => 1,
            'coupon_id'      => $this->input('form.coupon_id'),
            'coupon_name'    => $coupon->name,
            'coupon_value'   => $coupon->coupon_value,
            'balance'        => $coupon->coupon_value,
            'customer_id'    => $this->input('form.customer_id'),
            'number'         => $this->input('form.number'),
            'sales_price'    => $this->input('form.sales_price'),
            'integrals'      => $this->input('form.integrals'),
            'expire_time'    => $coupon->end,
            'rate'           => $coupon->rate,
            'department_id'  => 2,  // 结算科室
            'salesman'       => $this->input('form.salesman'),
            'create_user_id' => user()->id,
        ];
    }

    /**
     * 收费通知单
     * @param $issue
     * @return array
     */
    public function cashierData($issue): array
    {
        return [
            'customer_id' => $this->input('form.customer_id'),
            'status'      => 2, // 已收款
            'payable'     => $this->input('form.sales_price'),
            'income'      => $this->input('form.sales_price'),
            'deposit'     => 0,
            'coupon'      => 0,
            'arrearage'   => 0,
            'user_id'     => user()->id,
            'operator'    => user()->id,
            'detail'      => $issue
        ];
    }

    /**
     * 付款账户信息
     * @return array
     */
    public function payData(): array
    {
        $data = [];

        foreach ($this->input('pay') as $p) {
            $data[] = [
                'customer_id' => $this->input('form.customer_id'),
                'accounts_id' => $p['accounts_id'],
                'income'      => $p['income'],
                'remark'      => $p['remark'] ?? null,
                'user_id'     => user()->id
            ];
        }

        return $data;
    }

    /**
     * 扣减积分数据
     * @param $couponDetail
     * @param $customer
     * @return array
     */
    public function integralsData($couponDetail, $customer): array
    {
        return [
            'type'     => 4,    // 积分类型:积分换券
            'type_id'  => $couponDetail->coupon_id,
            'before'   => $customer->integral,
            'integral' => -1 * abs($couponDetail->integrals),
            'after'    => $customer->integral - $couponDetail->integrals,
            'remark'   => "使用{$couponDetail->integrals}积分换券",
            'data'     => $couponDetail
        ];
    }

    /**
     * 营收明细
     * @param $cashier
     * @param $detail
     * @return array
     */
    public function cashierDetailData($cashier, $detail)
    {
        return [
            'customer_id'      => $cashier->customer_id,
            'cashierable_type' => $cashier->cashierable_type,
            'table_name'       => 'coupon_details',
            'table_id'         => $detail->id,
            'package_id'       => null,
            'package_name'     => null,
            'product_id'       => 2,
            'product_name'     => '购卡换券',
            'goods_id'         => null,
            'goods_name'       => null,
            'times'            => 1,
            'unit_id'          => null,
            'specs'            => null,
            'payable'          => $detail->sales_price,
            'income'           => $detail->sales_price,
            'arrearage'        => 0,
            'deposit'          => 0,
            'coupon'           => 0,
            'department_id'    => 2, // 财务科
            'salesman'         => $this->input('form.salesman'),    // 销售人员
            'user_id'          => user()->id,
        ];
    }

    /**
     * 客户项目明细表
     * @param $cashierDetail
     * @param $product
     * @return array
     */
    public function customerProduct($cashierDetail, $product): array
    {
        $data = [];

        foreach ($cashierDetail as $detail) {
            $data[] = [
                'cashier_id'        => $detail->cashier_id,
                'cashier_detail_id' => $detail->id,
                'customer_id'       => $detail->customer_id,
                'product_id'        => 2,
                'product_name'      => $product->name,
                'package_id'        => null,
                'package_name'      => null,
                'status'            => 2,                                           // 项目状态(不需要划扣)
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
                'reception_type'    => 5,                                           // 分诊状态:其他
                'medium_id'         => 2,                                           // 媒介来源:暂定,员工推荐
                'department_id'     => $detail->department_id,                      // 结算科室
                'deduct_department' => $product->deduct_department,                 // 划扣科室
                'salesman'          => $detail->salesman
            ];
        }

        return $data;
    }

    /**
     * 写入消费积分
     * @param $cashierDetails
     * @param $product
     * @param $customer
     * @return array
     */
    public function customerIntegrals($cashierDetails, $product, $customer): array
    {
        $data = [];

        foreach ($cashierDetails as $detail) {
            $rate         = parameter('cywebos_integral_rate'); // 积分比例
            $integral     = ($detail->income + $detail->deposit) * $rate; // 计算当前项目积分
            $integralable = $product->integral; // 是否开启积分

            $insertData = [
                'customer_id' => $detail->customer_id,
                'type'        => 2,                                 // 项目消费赠送积分
                'type_id'     => $detail->cashier_id,               // 业务单号
                'before'      => $customer->integral,               // 原有积分
                'integral'    => $integral,                         // 变动积分
                'after'       => $customer->integral + $integral,   // 现有积分
                'remark'      => "购卡换券：实收金额:{$detail->income}",
                'data'        => $detail
            ];

            // 充值 没有开启积分 或 系统关闭积分功能
            if (!$integralable || !parameter('cywebos_integral_enable')) {
                $insertData['integral'] = 0;
            }

            // 赋值
            $data[] = $insertData;
        }

        return $data;
    }

    /**
     * 业绩表
     * @param $coupon
     * @param $cashier
     * @param $cashierDetail
     * @param $product
     * @param $customer
     * @return array
     */
    public function salesPerformances($coupon, $cashier, $cashierDetail, $product, $customer): array
    {
        $data = [];

        foreach ($cashierDetail as $detail) {

            // 销售人员提成
            if (is_array($detail->salesman) && count($detail->salesman)) {
                foreach ($detail->salesman as $v) {
                    // 提成金额
                    $amount = $product->commission ? ($detail->income * $v['rate']) / 100 : 0;
                    $data[] = [
                        'cashier_id'     => $detail->cashier_id,
                        'customer_id'    => $detail->customer_id,
                        'position'       => 1, // 销售提成
                        'table_name'     => $cashier->cashierable_type,
                        'table_id'       => $cashier->cashierable_id,
                        'user_id'        => $v['user_id'],
                        'reception_type' => 5,  // 分诊状态:其他
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
                        'remark'         => $coupon->integrals ? "使用{$coupon->integrals}积分换券,实收金额:{$detail->income}计提金额:{$amount}" : "实收金额:{$detail->income}计提金额:{$amount}"
                    ];
                }
            }

            // 开发人员
            if ($customer->ascription) {
                // 提成金额
                $amount = $product->commission ? $detail->income : 0;
                $data[] = [
                    'cashier_id'     => $detail->cashier_id,
                    'customer_id'    => $detail->customer_id,
                    'position'       => 2,
                    'table_name'     => $cashier->cashierable_type,
                    'table_id'       => $cashier->cashierable_id,
                    'user_id'        => $customer->ascription,
                    'reception_type' => 5,  // 分诊状态:其他
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
                    'remark'         => $coupon->integrals ? "使用{$coupon->integrals}积分换券,实收金额:{$detail->income}计提金额:{$amount}" : "实收金额:{$detail->income}计提金额:{$amount}"
                ];
            }
        }

        return $data;
    }

    /**
     * 更新顾客信息
     * @param $customer
     * @param $coupon
     * @param $cashierDetail
     * @param $product
     * @return array
     */
    public function customerData($customer, $coupon, $cashierDetail, $product): array
    {
        $detail       = $cashierDetail->first();
        $rate         = parameter('cywebos_integral_rate'); // 积分比例
        $integral     = ($detail->income + $detail->deposit) * $rate; // 计算当前项目积分
        $integralable = $product->integral; // 是否开启积分

        // 更新数据
        $updateData = [
            'integral'        => $customer->integral,
            'total_payment'   => $customer->total_payment,
            'expend_integral' => $customer->expend_integral
        ];

        // 积分换券
        if ($coupon->integrals) {
            $updateData['integral']        = bcsub($updateData['integral'], $coupon->integrals, 4);         // 扣掉积分
            $updateData['expend_integral'] = bcadd($updateData['expend_integral'], $coupon->integrals, 4);  // 加上累计使用积分
        }

        // 卡券零售
        if ($coupon->sales_price) {
            // 购卡换券开启消费积分 && 系统开启积分
            if ($integralable && parameter('cywebos_integral_enable')) {
                $updateData['integral'] = bcadd($updateData['integral'], $integral, 4);
            }
            $updateData['total_payment'] = bcadd($customer->total_payment, $coupon->sales_price, 4);    // 加上增累计付款
        }

        return $updateData;
    }
}
