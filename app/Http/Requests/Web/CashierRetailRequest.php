<?php

namespace App\Http\Requests\Web;

use App\Enums\CashierRetailStatus;
use App\Models\CashierRetail;
use App\Models\Customer;
use App\Rules\Web\SceneRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CashierRetailRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return match (request()->route()->getActionMethod()) {
            'manage' => $this->getManageRules(),
            'info' => $this->getInfoRules(),
            'remove' => $this->getRemoveRules(),
            'pending' => $this->getPendingRules(),
            'charge' => $this->getChargeRules(),
            default => []
        };
    }

    public function messages(): array
    {
        return match (request()->route()->getActionMethod()) {
            'manage' => $this->getManageMessages(),
            'info' => $this->getInfoMessages(),
            'remove' => $this->getRemoveMessages(),
            'pending' => $this->getPendingMessages(),
            'charge' => $this->getChargeMessages(),
            default => []
        };
    }

    private function getManageMessages(): array
    {
        return [];
    }

    private function getInfoMessages(): array
    {
        return [
            'id.required' => 'ID不能为空!',
            'id.exists' => '没有找到对应的零售单!',
        ];
    }

    private function getRemoveMessages(): array
    {
        return [
            'id.required' => 'ID不能为空!',
            'id.exists' => '没有找到要删除的单据!',
        ];
    }

    private function getPendingMessages(): array
    {
        return [
            'customer_id.required' => '缺少customer_id参数!',
            'customer_id.exists' => '患者不存在!',
            'medium_id.required' => '结算方式不能为空!',
            'type.required' => '零售类型不能为空!',
        ];
    }

    private function getChargeMessages(): array
    {
        return [
            'id.exists' => '订单状态错误!',
            'customer_id.required' => '请选择消费顾客!',
            'customer_id.exists' => '没有找到顾客信息!',
            'medium_id.required' => '媒介来源不能为空',
            'medium_id.exists' => '没有找到媒介来源',
        ];
    }

    private function getManageRules(): array
    {
        return [
            'date' => 'required|array|size:2',
            'date.*' => 'required|date|date_format:Y-m-d',
            'sort' => [
                'nullable',
                'string',
                Rule::in([
                    'id',
                    'cashier_id',
                    'customer_id',
                    'type',
                    'payable',
                    'income',
                    'deposit',
                    'arrearage',
                    'user_id',
                    'medium_id',
                    'remark',
                    'status',
                    'created_at',
                    'updated_at',
                ]),
            ],
            'order' => 'nullable|string|in:asc,desc',
            'rows' => 'nullable|integer|min:1|max:1000',
            'keyword' => 'nullable|string|max:255',
            'filters' => ['nullable', 'array', new SceneRule('CashierRetailIndex')],
            'filters.*' => 'required|array',
            'filters.*.field' => 'required|string',
            'filters.*.operator' => 'required|string',
            'filters.*.value' => 'nullable',
        ];
    }

    private function getInfoRules(): array
    {
        return [
            'id' => 'required|exists:cashier_retail',
        ];
    }

    private function getRemoveRules(): array
    {
        return [
            'id' => [
                'required',
                'exists:cashier_retail',
                function ($attribute, $value, $fail) {
                    if (CashierRetail::where('id', $value)->where('status', CashierRetailStatus::DEAL->value)->count()) {
                        $fail('零售单已收费,无法删除!');
                    }
                },
            ],
        ];
    }

    private function getPendingRules(): array
    {
        return [
            'customer_id' => 'required|exists:customer,id',
            'medium_id' => 'required',
            'type' => 'required',
        ];
    }

    private function getChargeRules(): array
    {
        return [
            'id' => [
                'nullable',
                'exists:cashier_retail',
                Rule::exists('cashier_retail')->where(function ($query) {
                    $query->where('status', CashierRetailStatus::PENDING->value);
                }),
            ],
            'customer_id' => [
                'required',
                'exists:customer,id',
                function ($attribute, $customer_id, $fail) {
                    $pay = collect($this->input('pay'));
                    $detail = collect($this->input('detail'));

                    if ($pay->pluck('accounts_id')->unique()->count() !== $pay->pluck('accounts_id')->count()) {
                        $fail('收款账户不能重复!');

                        return;
                    }

                    if ($detail->where('product_id', 1)->count() > 1) {
                        $fail('【预收费用】重复!');

                        return;
                    }

                    if ($detail->where('product_id', 1)->count() && ! $pay->count()) {
                        $fail('【预收费用】项目必须收费!');

                        return;
                    }

                    if ($detail->where('product_id', 1)->count() && $detail->where('product_id', 1)->sum('payable') > $pay->where('accounts_id', '<>', 1)->sum('income')) {
                        $fail('【实收金额】必须大于【预收费用】!');

                        return;
                    }

                    if ($pay->where('accounts_id', 1)->sum('income') > Customer::find($customer_id)->balance) {
                        $fail('账户余额不够支付');

                        return;
                    }
                },
            ],
            'medium_id' => 'required|exists:medium,id',
        ];
    }

    public function fillData(): array
    {
        return match (request()->route()->getActionMethod()) {
            'charge' => $this->fillChargeData(),
            default => $this->fillPendingData(),
        };
    }

    public function detailsData(): array
    {
        $data = [];

        foreach ($this->input('detail') as $k => $v) {
            $data[] = [
                'customer_id' => $this->input('customer_id'),
                'type' => $v['type'],
                'package_id' => $v['package_id'] ?? null,
                'package_name' => $v['package_name'] ?? null,
                'splitable' => $v['splitable'] ?? null,
                'product_id' => $v['product_id'] ?? null,
                'product_name' => $v['product_name'] ?? null,
                'goods_id' => $v['goods_id'] ?? null,
                'goods_name' => $v['goods_name'] ?? null,
                'times' => $v['times'],
                'unit_id' => $v['unit_id'] ?? null,
                'specs' => $v['specs'] ?? null,
                'price' => $v['price'],
                'sales_price' => $v['sales_price'],
                'payable' => $v['payable'],
                'amount' => 0,
                'department_id' => $v['department_id'],
                'salesman' => $v['salesman'],
                'remark' => $v['remark'],
                'user_id' => user()->id,
            ];
        }

        return $data;
    }

    private function fillPendingData(): array
    {
        $payable = collect($this->input('detail'))->sum('payable');

        return [
            'customer_id' => $this->input('customer_id'),
            'medium_id' => $this->input('medium_id'),
            'type' => $this->input('type'),
            'status' => CashierRetailStatus::PENDING,
            'payable' => $payable,
            'remark' => $this->input('remark'),
            'user_id' => user()->id,
        ];
    }

    private function fillChargeData(): array
    {
        $payable = collect($this->input('detail'))->sum('payable');
        $income = collect($this->input('pay'))->where('accounts_id', '<>', 1)->sum('income');
        $deposit = collect($this->input('pay'))->where('accounts_id', 1)->sum('income');
        $arrearage = $payable - $income - $deposit;

        return [
            'customer_id' => $this->input('customer_id'),
            'medium_id' => $this->input('medium_id'),
            'type' => $this->input('type'),
            'status' => CashierRetailStatus::DEAL,
            'payable' => $payable,
            'income' => $income,
            'deposit' => $deposit,
            'arrearage' => $arrearage,
            'remark' => $this->input('remark'),
            'user_id' => user()->id,
        ];
    }

    public function cashierData($detail): array
    {
        $payable = collect($this->input('detail'))->sum('payable');
        $income = collect($this->input('pay'))->where('accounts_id', '<>', 1)->sum('income');
        $deposit = collect($this->input('pay'))->where('accounts_id', 1)->sum('income');
        $arrearage = $payable - $income - $deposit;

        return [
            'customer_id' => $this->input('customer_id'),
            'status' => 1, // 未收费(兼容问题)
            'payable' => $payable,
            'income' => $income,
            'deposit' => $deposit,
            'arrearage' => $arrearage,
            'user_id' => user()->id,
            'operator' => user()->id,
            'detail' => $detail,
        ];
    }

    public function payData(): array
    {
        $data = [];
        $pay = $this->input('pay');

        foreach ($pay as $p) {
            $data[] = [
                'customer_id' => $this->input('customer_id'),
                'accounts_id' => $p['accounts_id'],
                'income' => $p['income'],
                'remark' => $p['remark'] ?? null,
                'user_id' => user()->id,
            ];
        }

        return $data;
    }

    public function CashierDetailData($cashier): array
    {
        $data = [];
        $detail = collect($cashier->detail)->sortBy('product_id');                        // 按项目排序
        $paycount = collect($cashier->pay)->where('accounts_id', '<>', 1)->sum('income');   // 实收金额(不包括余额支付)
        $balance = collect($cashier->pay)->where('accounts_id', 1)->sum('income');         // 余额支付费用
        $amount = $paycount + $balance;                                                   // 合计支付费用

        // 费用摊到各个项目上
        foreach ($detail as $k => $v) {
            $income = 0; // 本单实收金额
            $deposit = 0; // 本单余额支付
            $arrearage = 0; // 本单欠款金额

            if ($amount) {
                if ($amount >= $v['payable']) {
                    // 预收费用,使用{实收金额}结算
                    if ($v['product_id'] == 1) {
                        $income = $v['payable'];
                        $deposit = 0;
                    } // 实收 && 实收 > 项目价格
                    elseif ($paycount && $paycount >= $v['payable']) {
                        $income = $v['payable'];
                        $deposit = 0;
                    } // 实收 && 实收 < 项目价格
                    elseif ($paycount && $paycount < $v['payable']) {
                        $income = $paycount;
                        $deposit = $v['payable'] - $paycount;
                    } else {
                        $income = 0;
                        $deposit = $v['payable'];
                    }
                } else {
                    $income = $paycount ? $paycount : 0;
                    $deposit = $balance ? $balance : 0;
                }
                $arrearage = $amount > $v['payable'] ? 0 : $v['payable'] - $amount;
            } else {
                $income = 0;
                $deposit = 0;
                $arrearage = $v['payable'];
            }

            // 扣减
            $paycount -= $income;
            $balance -= $deposit;
            $amount -= ($income + $deposit);

            $data[] = [
                'customer_id' => $cashier->customer_id,
                'cashierable_type' => $cashier->cashierable_type,
                'table_name' => 'cashier_retail_detail',
                'table_id' => $v['id'],
                'package_id' => $v['package_id'] ?? null,
                'package_name' => $v['package_name'] ?? null,
                'product_id' => $v['product_id'] ?? null,
                'product_name' => $v['product_name'] ?? null,
                'goods_id' => $v['goods_id'] ?? null,
                'goods_name' => $v['goods_name'] ?? null,
                'times' => $v['times'] ?? null,
                'unit_id' => $v['unit_id'] ?? null,
                'specs' => $v['specs'] ?? null,
                'payable' => $v['payable'],
                'income' => $income,
                'arrearage' => $arrearage,
                'deposit' => $deposit,
                'department_id' => $v['department_id'],
                'salesman' => $v['salesman'],
                'user_id' => user()->id,
            ];
        }

        return $data;
    }
}
