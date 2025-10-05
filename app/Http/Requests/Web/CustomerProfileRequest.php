<?php

namespace App\Http\Requests\Web;

use Illuminate\Foundation\Http\FormRequest;

class CustomerProfileRequest extends FormRequest
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
        return match (request()->route()->getActionMethod()) {
            'info',
            'overview',
            'log',
            'sms',
            'talk',
            'erkai',
            'photo',
            'coupons',
            'appointment',
            'reservation',
            'consultant',
            'product',
            'followup',
            'treatment',
            'qufriend',
            'cashier',
            'goods' => $this->getInfoRules(),
            'phone' => $this->getPhoneRules(),
            default => []
        };
    }

    private function getInfoRules(): array
    {
        return [
            'customer_id' => 'required|string|exists:customer,id',
        ];
    }

    public function messages(): array
    {
        return match (request()->route()->getActionMethod()) {
            'info',
            'overview',
            'log',
            'sms',
            'talk',
            'erkai',
            'photo',
            'coupons',
            'appointment',
            'reservation',
            'consultant',
            'product',
            'followup',
            'treatment',
            'qufriend',
            'cashier',
            'goods' => $this->getInfoMessages(),
            'phone' => $this->getPhoneMessages(),
            default => []
        };
    }

    private function getInfoMessages(): array
    {
        return [
            'customer_id.required' => '客户ID不能为空',
            'customer_id.string'   => '客户ID必须是字符串',
            'customer_id.exists'   => '客户ID不存在',
        ];
    }

    private function getPhoneRules(): array
    {
        return [
            'customer_id' => 'required|string|exists:customer,id',
            'id'          => 'nullable|string|exists:customer_phones,id',
        ];
    }

    private function getPhoneMessages(): array
    {
        return [
            'customer_id.required' => '客户ID不能为空',
            'customer_id.string'   => '客户ID必须是字符串',
            'customer_id.exists'   => '客户ID不存在',
            'id.string'            => '电话ID必须是字符串',
            'id.exists'            => '电话ID不存在',
        ];
    }
}
