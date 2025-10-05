<?php

namespace App\Http\Requests\Cashier;

use App\Rules\Cashier\ChargeRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * 收费表单请求
 */
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
     * 验证规则
     * @return array
     */
    public function rules(): array
    {
        return [
            'id'                => [
                'required',
                'exists:cashier,id,status,1',
                new ChargeRule($this->input('pay'), $this->input('detail'))
            ],
            'pay'               => 'nullable|array',
            'pay.*.accounts_id' => 'required|exists:accounts,id',
            'pay.*.income'      => 'required|numeric',
        ];
    }

    public function messages(): array
    {
        return [
            'id.required' => '缺少id参数',
            'id.exists'   => '业务状态错误！'
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
     * 获取收银员修改的数据
     * @param $cashier
     * @return array
     */
    public function getDetailChanges($cashier): array
    {
        $inserted = [];
        $deleted  = [];
        $updated  = [];

        // 现场咨询单
        if ($cashier->cashierable_type == 'App\Models\Consultant') {
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
        }

        return [
            'inserted' => $inserted,
            'deleted'  => $deleted,
            'updated'  => $updated
        ];
    }

    /**
     * 营收明细
     * @param $cashier
     * @return array
     */
    public function CashierDetailData($cashier): array
    {
        if ($cashier->cashierable_type == 'App\Models\Consultant') {
            return $this->consultantDetailData($cashier);
        }
        if ($cashier->cashierable_type == 'App\Models\Outpatient') {
            return $this->outpatientDetailData($cashier);
        }
        if ($cashier->cashierable_type == 'App\Models\CashierRefund') {
            return $this->cashierRefundDetailData($cashier);
        }
        if ($cashier->cashierable_type == 'App\Models\Erkai') {
            return $this->erkaiDetailData($cashier);
        }
    }

    /**
     * 现场咨询单
     * @param $cashier
     * @return array
     */
    public function consultantDetailData($cashier): array
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
                'income'           => $income,
                'arrearage'        => $arrearage,
                'deposit'          => $deposit,
                'coupon'           => 0,
                'department_id'    => $v['department_id'],
                'salesman'         => $v['salesman'],
                'user_id'          => user()->id,
            ];
        }

        return $data;
    }

    /**
     * 医生门诊单
     * @param $cashier
     * @return array
     */
    public function outpatientDetailData($cashier): array
    {
        $data     = [];
        $paycount = collect($cashier->pay)->where('accounts_id', '<>', 1)->sum('income');   // 实收金额(不包括余额支付)
        $balance  = collect($cashier->pay)->where('accounts_id', 1)->sum('income');         // 余额支付费用
        $amount   = $paycount + $balance;


        $prescriptions = collect($cashier->detail['prescriptions'])->pluck('details')->collapse();   // 处方记录

        // 费用平摊到各个处方上
        foreach ($prescriptions as $k => $v) {
            $income    = 0; // 本单实收金额
            $deposit   = 0; // 本单余额支付
            $arrearage = 0; // 本单欠款金额

            if ($amount) {
                if ($amount >= $v['amount']) {
                    if ($paycount && $paycount >= $v['amount']) {
                        $income  = $v['amount'];
                        $deposit = 0;
                    } // 实收 && 实收 < 项目价格
                    elseif ($paycount && $paycount < $v['amount']) {
                        $income  = $paycount;
                        $deposit = $v['amount'] - $paycount;
                    } else {
                        $income  = 0;
                        $deposit = $v['amount'];
                    }
                } else {
                    $income  = $paycount ? $paycount : 0;
                    $deposit = $balance ? $balance : 0;
                }
                $arrearage = $amount > $v['amount'] ? 0 : $v['amount'] - $amount;
            } else {
                $income    = 0;
                $deposit   = 0;
                $arrearage = $v['amount'];
            }

            // 扣减
            $paycount -= $income;
            $balance  -= $deposit;
            $amount   -= ($income + $deposit);

            $data[] = [
                'customer_id'      => $cashier->customer_id,
                'cashierable_type' => $cashier->cashierable_type,
                'table_name'       => 'outpatient_prescription_detail',
                'table_id'         => $v['id'],
                'package_id'       => $v['package_id'] ?? null,
                'package_name'     => $v['package_name'] ?? null,
                'product_id'       => $v['product_id'] ?? null,
                'product_name'     => $v['product_name'] ?? null,
                'goods_id'         => $v['goods_id'] ?? null,
                'goods_name'       => $v['goods_name'] ?? null,
                'times'            => $v['number'],
                'unit_id'          => $v['goods_unit'],
                'specs'            => $v['specs'] ?? null,
                'payable'          => $v['amount'],
                'income'           => $income,
                'arrearage'        => $arrearage,
                'deposit'          => $deposit,
                'department_id'    => $cashier->cashierable->department_id, // 门诊病历-就诊科室
                'salesman'         => [
                    [
                        'user_id' => user()->id,
                        'rate'    => 100
                    ]
                ],
                'user_id'          => user()->id,
            ];
        }

        return $data;
    }

    /**
     * 退款明细
     * @param $cashier
     * @return array
     */
    public function cashierRefundDetailData($cashier): array
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
     * 二开零售
     * @param $cashier
     * @return array
     */
    public function erkaiDetailData($cashier): array
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
        $detail    = null;

        // 现场咨询单
        if ($cashier->cashierable_type == 'App\Models\Consultant') {
            $detail = $cashier->cashierable->orders()->whereIn('id', collect($cashier->detail)->pluck('id'))->get();
        }

        // 医生门诊单
        if ($cashier->cashierable_type == 'App\Models\Outpatient') {
//            $detail = [
//                 'prescriptions' =>
//            ];
            $detail = $cashier->detail;
        }

        // 退款申请
        if ($cashier->cashierable_type == 'App\Models\CashierRefund') {
            $detail = $cashier->detail;
        }

        // 二开零售
        if ($cashier->cashierable_type == 'App\Models\Erkai') {
            $detail = $cashier->cashierable->details;
        }

        return [
            'status'    => 2,
            'income'    => $income,
            'deposit'   => $deposit,
            'arrearage' => $arrearage,
            'operator'  => user()->id,
            'detail'    => $detail
        ];
    }
}
