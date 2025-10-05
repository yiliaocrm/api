<?php

namespace App\Http\Requests\Web;

use Illuminate\Foundation\Http\FormRequest;

class CashierPayRequest extends FormRequest
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
            'update' => $this->getUpdateRules(),
            default => []
        };
    }

    private function getUpdateRules(): array
    {
        return [
            'id'          => 'required|exists:cashier_pay',
            'accounts_id' => 'nullable|numeric|not_in:1|exists:accounts,id',
            'remark'      => 'nullable'
        ];
    }

    /**
     * 自定义出错信息
     * @return array|string[]
     */
    public function messages(): array
    {
        return match (request()->route()->getActionMethod()) {
            'update' => $this->getUpdateMessages(),
            default => []
        };
    }

    private function getUpdateMessages(): array
    {
        return [
            'accounts_id.not_in' => '[支付方式]不能改为余额支付!',
        ];
    }

    /**
     * 表单数据
     * @return array
     */
    public function formData(): array
    {
        return match (request()->route()->getActionMethod()) {
            'update' => $this->getUpdateFormData(),
            default => []
        };
    }

    private function getUpdateFormData(): array
    {
        $data = [];

        if ($this->input('accounts_id')) {
            $data['accounts_id'] = $this->input('accounts_id');
        }

        if ($this->has('remark')) {
            $data['remark'] = $this->input('remark');
        }

        return $data;
    }
}
