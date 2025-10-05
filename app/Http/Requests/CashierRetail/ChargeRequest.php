<?php

namespace App\Http\Requests\CashierRetail;

use Illuminate\Validation\Rule;
use App\Rules\CashierRetail\ChargeRule;
use Illuminate\Foundation\Http\FormRequest;

class ChargeRequest extends FormRequest
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
            'id'          => [
                'nullable',
                'exists:cashier_retail',
                Rule::exists('cashier_retail')->where(function ($query) {
                    $query->where('status', 1);
                })
            ],
            'customer_id' => [
                'required',
                'exists:customer,id',
                new ChargeRule($this->input('pay'), $this->input('detail'))
            ],
            'medium_id'   => 'required|exists:medium,id',
        ];
    }

    public function messages(): array
    {
        return [
            'id.exists'            => '订单状态错误!',
            'customer_id.required' => '请选择消费顾客!',
            'customer_id.exists'   => '没有找到顾客信息!',
            'medium_id.required'   => '媒介来源不能为空',
            'medium_id.exists'     => '没有找到媒介来源',
        ];
    }

    /**
     * 零售单主单数据
     * @return array
     */
    public function fillData(): array
    {
        $payable   = collect($this->input('detail'))->sum('payable');
        $income    = collect($this->input('pay'))->where('accounts_id', '<>', 1)->sum('income');
        $deposit   = collect($this->input('pay'))->where('accounts_id', 1)->sum('income');
        $arrearage = $payable - $income - $deposit;

        return [
            'customer_id' => $this->input('customer_id'),
            'medium_id'   => $this->input('medium_id'),
            'type'        => $this->input('type'),
            'status'      => 2,
            'payable'     => $payable,
            'income'      => $income,
            'deposit'     => $deposit,
            'arrearage'   => $arrearage,
            'remark'      => $this->input('remark'),
            'detail'      => $this->input('detail'),
            'user_id'     => user()->id,
        ];
    }

    /**
     * 零售明细单
     * @return array
     */
    public function detailsData(): array
    {
        $data = [];

        foreach ($this->input('detail') as $k => $v) {
            $data[] = [
                'customer_id'   => $this->input('customer_id'),
                'type'          => $v['type'],
                'package_id'    => $v['package_id'] ?? null,
                'package_name'  => $v['package_name'] ?? null,
                'splitable'     => $v['splitable'] ?? null,
                'product_id'    => $v['product_id'] ?? null,
                'product_name'  => $v['product_name'] ?? null,
                'goods_id'      => $v['goods_id'] ?? null,
                'goods_name'    => $v['goods_name'] ?? null,
                'times'         => $v['times'],
                'unit_id'       => $v['unit_id'] ?? null,
                'specs'         => $v['specs'] ?? null,
                'price'         => $v['price'],
                'sales_price'   => $v['sales_price'],
                'payable'       => $v['payable'],
                'amount'        => 0,
                'department_id' => $v['department_id'],
                'salesman'      => $v['salesman'],
                'remark'        => $v['remark'],
                'user_id'       => user()->id
            ];
        }

        return $data;
    }

    /**
     * 收费通知单数据
     * @param $detail
     * @return array
     */
    public function cashierData($detail): array
    {
        $payable   = collect($this->input('detail'))->sum('payable');
        $income    = collect($this->input('pay'))->where('accounts_id', '<>', 1)->sum('income');
        $deposit   = collect($this->input('pay'))->where('accounts_id', 1)->sum('income');
        $arrearage = $payable - $income - $deposit;

        return [
            'customer_id' => $this->input('customer_id'),
            'status'      => 1, // 未收费(兼容问题)
            'payable'     => $payable,
            'income'      => $income,
            'deposit'     => $deposit,
            'arrearage'   => $arrearage,
            'user_id'     => user()->id,
            'operator'    => user()->id,
            'detail'      => $detail,
        ];
    }

    /**
     * 支付信息
     * @return array
     */
    public function payData(): array
    {
        $data = [];
        $pay  = $this->input('pay');

        foreach ($pay as $p) {
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

    /**
     * 营收明细数据
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
                'table_name'       => 'cashier_retail_detail',
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
}
