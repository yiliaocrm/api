<?php

namespace App\Http\Requests\Print;

use App\Models\Cashier;
use App\Models\PrintTemplate;
use Illuminate\Foundation\Http\FormRequest;

class CashierRequest extends FormRequest
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
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'id'   => [
                'required',
                'exists:cashier,id',
                function ($attribute, $value, $fail) {
                    $cashier = $this->getCashier();
                    if ($cashier->status != 2) {
                        $fail('收费单状态不正确');
                    }
                }
            ],
            'type' => [
                'required',
                'in:charge_detail,charge_detail_invoice,cashier_refund,cashier_refund_invoice,cashier_repayment,cashier_repayment_invoice,cashier_retail',
                function ($attribute, $value, $fail) {
                    if (!$this->getPrintTemplate()) {
                        $fail('默认打印模板不存在');
                    }
                }
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'id.required'   => '收费单ID不能为空',
            'id.exists'     => '收费单ID不存在',
            'type.required' => '模板类型不能为空',
        ];
    }

    public function getCashier(): ?Cashier
    {
        return Cashier::query()->find(
            $this->input('id')
        );
    }

    public function getPrintTemplate(): ?PrintTemplate
    {
        return PrintTemplate::query()
            ->where('type', $this->input('type'))
            ->where('default', 1)
            ->first();
    }

    /**
     * 获取支付方式
     * @return string
     */
    public function getPaymentMethods(): string
    {
        return $this->getCashier()->pays->map(function ($pay) {
            return $pay->account->name . ':' . $pay->income;
        })->implode('、');
    }

    /**
     * 获取收费项目类别
     * @return mixed
     */
    public function getExpenseCategories(): mixed
    {
        return $this->getCashier()->details->mapToGroups(function ($detail) {
            if ($detail->product) {
                return [$detail->product->expenseCategory->name => $detail->payable];
            }
            return [$detail->goods->expenseCategory->name => $detail->payable];
        })->map(function ($group) {
            return $group->sum();
        });
    }
}
