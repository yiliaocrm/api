<?php

namespace App\Http\Requests\Cashier;

use Carbon\Carbon;
use App\Models\Cashier;
use App\Models\Integral;
use App\Models\Customer;
use App\Models\CustomerGoods;
use App\Models\ReceptionOrder;
use App\Models\CustomerProduct;
use App\Models\SalesPerformance;
use App\Models\CustomerDepositDetail;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Http\FormRequest;

class RefundChargeRequest extends FormRequest
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
                function ($attribute, $cashier_id, $fail) {
                    $pay      = collect($this->input('pay'));
                    $detail   = collect($this->input('detail'));
                    $cashier  = Cashier::query()->find($cashier_id);
                    $customer = Customer::query()->find($cashier->customer_id);

                    if (!$cashier) {
                        return $fail('没有找到收费单记录!');
                    }

                    // 判断金额
                    if (bccomp($pay->sum('income'), $detail->sum('amount'), 4) != 0) {
                        return $fail('《支付金额》与《合计应收》不一致!');
                    }

                    // 支付方式大于1, 并且 其中有金额为0
                    if ($pay->pluck('accounts_id')->count() > 1 && $pay->where('income', 0)->count()) {
                        return $fail('【支付方式】收款金额不能为0!');
                    }

                    // 充值退款 && 并且账号余额不够退款
                    $amount = abs($cashier->cashierable->details->where('product_id', 1)->sum('amount'));
                    if ($amount && $customer->balance < $amount) {
                        return $fail('[账户余额]不足!');
                    }
                }
            ],
            'pay'               => 'nullable|array',
            'pay.*.accounts_id' => 'required|distinct|exists:accounts,id'
        ];
    }

    public function messages(): array
    {
        return [
            'id.required'                => '缺少id参数',
            'pay.*.accounts_id.distinct' => '收款账户不能重复!',
            'pay.*.income.required'      => '[收款金额]不能为空!'
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
        $data   = [];
        $amount = abs(collect($cashier->pay)->sum('income'));    // 总退款金额
        $detail = $cashier->detail;

        foreach ($detail as $k => $v) {
            $data[] = [
                'customer_id'      => $cashier->customer_id,
                'cashierable_type' => $cashier->cashierable_type,
                'table_name'       => $v['customer_product_id'] ? 'customer_product' : 'customer_goods',
                'table_id'         => $v['customer_product_id'] ?? $v['customer_goods_id'],
                'package_id'       => $v['package_id'] ?? null,
                'package_name'     => $v['package_name'] ?? null,
                'product_id'       => $v['product_id'] ?? null,
                'product_name'     => $v['product_name'] ?? null,
                'goods_id'         => $v['goods_id'] ?? null,
                'goods_name'       => $v['goods_name'] ?? null,
                'times'            => $v['times'],
                'unit_id'          => $v['goods_unit'] ?? null,
                'specs'            => $v['specs'] ?? null,
                'payable'          => $v['amount'],
                'income'           => $v['amount'],
                'arrearage'        => 0,
                'deposit'          => 0,
                'department_id'    => $v['department_id'],
                'salesman'         => $v['salesman'],
                'user_id'          => user()->id,
            ];
            $amount -= abs($v['amount']);
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
     * 处理cashier_detail表
     * @param $cashier
     * @param $cashierDetail
     * @param $customer
     */
    public function handleCashierDetail($cashier, $cashierDetail, $customer)
    {
        // 分诊状态
        $reception_type = null;

        // 项目退款
        if ($cashierDetail->table_name == 'customer_product') {
            // 顾客项目表
            $customerProduct = CustomerProduct::query()->find(
                $cashierDetail->table_id
            );

            // 更新[顾客项目明细表]
            $income  = $customerProduct->income >= abs($cashierDetail->income) ? $customerProduct->income - abs($cashierDetail->income) : 0;
            $deposit = $customerProduct->deposit - (abs($cashierDetail->income) - $customerProduct->income);

            $customerProduct->update([
                'refund_times' => $customerProduct->refund_times + $cashierDetail->times,
                'leftover'     => $customerProduct->leftover ? $customerProduct->leftover - $cashierDetail->times : 0,
                'income'       => $income,
                'deposit'      => $deposit
            ]);
            $reception_type = $customerProduct->reception_type;
        }

        if ($cashierDetail->table_name == 'customer_goods') {
            // 顾客物品表
            $customerGoods = CustomerGoods::query()->find(
                $cashierDetail->table_id
            );

            // 更新[顾客物品明细表](没有测试过)
            $income  = $customerGoods->income >= abs($cashierDetail->income) ? $customerGoods->income - abs($cashierDetail->income) : 0;
            $deposit = $customerGoods->deposit - (abs($cashierDetail->income) - $customerGoods->income);

            $customerGoods->update([
                'refund_times' => $customerGoods->refund_times + $cashierDetail->times,
                'leftover'     => $customerGoods->leftover - $cashierDetail->times,
                'income'       => $income,
                'deposit'      => $deposit
            ]);
            $reception_type = $customerGoods->reception_type;
        }

        // 账户余额退款
        if ($cashierDetail->product_id == 1) {
            $after = bcadd($customer->balance, $cashierDetail->income, 4);
            CustomerDepositDetail::query()->create([
                'customer_id'       => $cashierDetail->customer_id,
                'cashier_id'        => $cashierDetail->cashier_id,
                'cashier_detail_id' => $cashierDetail->id,
                'before'            => $customer->balance,
                'balance'           => $cashierDetail->income,
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

        // 更新[现场咨询]状态:如果当天开单，当天退款,需要处理
        $this->handleConsultantStatus($cashierDetail);

        // 创建[顾客积分]
        $this->createCustomerIntegral($cashierDetail, $cashierDetail->product, $cashierDetail->goods, $customer);

        // 计算业绩
        $this->createSalesPerformance($cashier, $cashierDetail, $reception_type);
    }

    /**
     * 处理现场咨询单状态
     * @param $cashier
     * @param $detail
     */
    private function handleConsultantStatus($detail)
    {
        // 原收费单信息
        $cashierDetail = $detail->product_id ? $detail->customerProduct->cashierDetail : $detail->customerGoods->cashierDetail;
        $cashier       = $cashierDetail->cashier;

        // 更新{现场咨询订单表}为{退款} 时间限定 在当天
        if ($cashierDetail->cashierable_type == 'App\Models\Consultant' && $cashierDetail->table_name == 'reception_order' && $cashier->created_at->startOfDay()->diffInDays(Carbon::now()->startOfDay()) == 0) {
            // 更新订单为退款
            ReceptionOrder::query()->find($cashierDetail->table_id)->update([
                'status' => 5
            ]);
            // 项目全部退款,接诊状态改为未成交
            if ($cashier->cashierable->orders->count() == $cashier->cashierable->orders->where('status', 5)->count()) {
                $cashier->cashierable()->update([
                    'status' => 1
                ]);
            }
        }
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
     * 退款扣业绩
     * @param $cashier
     * @param $cashierDetail
     * @param $reception_type
     */
    private function createSalesPerformance($cashier, $cashierDetail, $reception_type)
    {
        // 找到原收费单id
        $customerGoods   = $cashierDetail->customerGoods;
        $customerProduct = $cashierDetail->customerProduct;
        $cashier_id      = $cashierDetail->product_id ? $customerProduct->cashier_id : $customerGoods->cashier_id;

        // 退款项目||物品信息
        $refundDetail = $cashier->cashierable
            ->details
            ->when($cashierDetail->product_id, function ($collection) use ($customerProduct) {
                return $collection->where('product_id', $customerProduct->product_id)->where('customer_product_id', $customerProduct->id);
            })
            ->when($cashierDetail->goods_id, function ($collection) use ($customerGoods) {
                return $collection->where('goods_id', $customerGoods->goods_id)->where('customer_goods_id', $customerGoods->id);
            })
            ->first();

        // 开发人员提成
        $ascriptionPerformance = SalesPerformance::query()
            ->where('position', 2)
            ->where('customer_id', $cashierDetail->customer_id)
            ->when($cashierDetail->product_id, function (Builder $query) use ($cashierDetail) {
                $query->where('product_id', $cashierDetail->product_id);
            })
            ->when($cashierDetail->goods_id, function (Builder $query) use ($cashierDetail) {
                $query->where('goods_id', $cashierDetail->goods_id);
            })
            ->where('cashier_id', $cashier_id)
            ->first();

        // 1.扣掉原开发人提成
        if ($ascriptionPerformance) {
            SalesPerformance::query()->create([
                'cashier_id'     => $cashier->id,
                'customer_id'    => $cashierDetail->customer_id,
                'position'       => 2,
                'table_name'     => $cashier->cashierable_type,
                'table_id'       => $cashier->cashierable_id,
                'user_id'        => $ascriptionPerformance->user_id, // 原开发人id
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
                'amount'         => $refundDetail->amount,
                'rate'           => 100,
                'remark'         => "顾客退款:实收金额:{$cashierDetail->income}计提金额:$cashierDetail->income"
            ]);
        }

        // 2.扣掉退款时,选择的销售人员业绩
        if (is_array($cashierDetail->salesman) && count($cashierDetail->salesman)) {
            foreach ($cashierDetail->salesman as $v) {
                SalesPerformance::query()->create([
                    'cashier_id'     => $cashier->id,
                    'customer_id'    => $cashierDetail->customer_id,
                    'position'       => 1,
                    'table_name'     => $cashier->cashierable_type,
                    'table_id'       => $cashier->cashierable_id,
                    'user_id'        => $v['user_id'], // 销售人员
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
                    'amount'         => ($refundDetail->amount * $v['rate']) / 100,
                    'rate'           => $v['rate'],
                    'remark'         => "顾客退款:实收金额:{$cashierDetail->income}计提金额:$cashierDetail->income"
                ]);
            }
        }


    }

    public function handleCashier($cashier, $customer)
    {
        // 更新 退款申请单(cashier_refund)
        $cashier->cashierable->update([
            'cashier_id' => $cashier->id,
            'status'     => 3
        ]);

        // 更新 退款申请单明细(cashier_refund_detail)
        $cashier->cashierable->details()->update([
            'cashier_id'      => $cashier->id,
            'cashier_user_id' => $cashier->operator
        ]);

        // 顾客信息
        $update = [
            'total_payment' => bcadd($customer->total_payment, $cashier->income, 4), // 累计付款
            'amount'        => bcadd($customer->amount, $cashier->cashierable->details->where('product_id', '<>', 1)->sum('amount'), 4), // 退款扣累计消费(不包含退预收)
        ];

        // 更新顾客信息
        $customer->update($update);
    }
}
