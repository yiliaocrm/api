<?php

namespace App\Http\Requests\CashierRefund;

use App\Models\Customer;
use App\Models\CustomerProduct;
use Illuminate\Foundation\Http\FormRequest;

class CreateRequest extends FormRequest
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
            'customer_id'         => 'required|exists:customer,id',
            'detail.*.product_id' => [
                'nullable',
                function ($attribute, $product_id, $fail) {
                    // 预收费用 && 金额为0
                    if ($product_id == 1 && !$this->input(str_replace('product_id', 'amount', $attribute))) {
                        return $fail('[预收费用]退款金额不能为0!');
                    }

                    $amount   = collect($this->input('detail'))->where('product_id', 1)->sum('amount');
                    $customer = Customer::query()->find($this->input('customer_id'));

                    // 退余额,判断可退余额
                    if ($customer->balance < $amount) {
                        return $fail('[账户余额]不足!');
                    }
                },
            ],
            'detail.*.amount'     => [
                'required',
                function ($attribute, $amount, $fail) {
                    $product_id          = $this->input(str_replace('amount', 'product_id', $attribute));
                    $customer_product_id = $this->input(str_replace('amount', 'customer_product_id', $attribute));

                    // 预收费用退款 && 大于 实收金额
                    if ($product_id && $product_id == 1 && $amount > CustomerProduct::query()->find($customer_product_id)->income) {
                        return $fail('预收费用退款金额不能大于实收金额');
                    }
                }
            ]
        ];
    }

    public function messages(): array
    {
        return [
            'customer_id.required' => '缺少customer_id参数',
            'customer_id.exists'   => '没有找到顾客信息',
        ];
    }

    public function formData(): array
    {
        return [
            'customer_id' => $this->input('customer_id'),
            'amount'      => collect($this->input('detail'))->sum('amount'),
            'remark'      => null,
            'user_id'     => user()->id,
            'status'      => 2, // 待收费
            'detail'      => $this->input('detail')
        ];
    }

    /**
     * 退款明细表
     * @param $refund
     * @return array
     */
    public function detailData($refund): array
    {
        $data    = [];
        $details = $this->input('detail');

        foreach ($details as $detail) {
            $data[] = [
                'status'              => $refund->status,
                'cashier_refund_id'   => $refund->id,
                'customer_id'         => $refund->customer_id,
                'cashier_id'          => null,
                'customer_product_id' => $detail['customer_product_id'] ?? null,
                'customer_goods_id'   => $detail['customer_goods_id'] ?? null,
                'package_id'          => $detail['package_id'] ?? null,
                'package_name'        => $detail['package_name'] ?? null,
                'product_id'          => $detail['product_id'] ?? null,
                'product_name'        => $detail['product_name'] ?? null,
                'goods_id'            => $detail['goods_id'] ?? null,
                'goods_name'          => $detail['goods_name'] ?? null,
                'times'               => $detail['times'],
                'unit_id'             => $detail['unit_id'] ?? null,
                'specs'               => $detail['specs'] ?? null,
                'department_id'       => $detail['department_id'],
                'amount'              => -1 * abs($detail['amount']),
                'salesman'            => $detail['salesman'],
                'user_id'             => user()->id,
                'cashier_user_id'     => null,
                'remark'              => $detail['remark'] ?? null,
            ];
        }

        return $data;
    }
}
