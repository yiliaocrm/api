<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class CustomerProfileRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return match (request()->route()->getActionMethod()) {
            'phone' => $this->getPhoneRules(),
            'profile', 'photo', 'overview', 'followup', 'reservation' => $this->getProfileRules(),
            default => []
        };
    }

    /**
     * Get the validation error messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return match (request()->route()->getActionMethod()) {
            'phone' => $this->getPhoneMessages(),
            'profile', 'photo', 'overview', 'followup', 'reservation' => $this->getProfileMessages(),
            default => []
        };
    }

    /**
     * 获取手机号验证规则
     */
    private function getPhoneRules(): array
    {
        return [
            'customer_id' => 'required|exists:customer,id',
            'id'          => 'nullable|exists:customer_phones,id',
        ];
    }

    /**
     * 获取手机号验证错误消息
     */
    private function getPhoneMessages(): array
    {
        return [
            'customer_id.required' => '顾客ID不能为空',
            'customer_id.exists'   => '顾客不存在',
            'id.exists'            => '手机号记录不存在',
        ];
    }

    private function getProfileRules(): array
    {
        return [
            'customer_id' => 'required|string|exists:customer,id',
        ];
    }

    private function getProfileMessages(): array
    {
        return [
            'customer_id.required' => '顾客ID不能为空',
            'customer_id.string'   => '顾客ID格式错误',
            'customer_id.exists'   => '顾客不存在',
        ];
    }
}
