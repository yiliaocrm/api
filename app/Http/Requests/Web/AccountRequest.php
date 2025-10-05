<?php

namespace App\Http\Requests\Web;

use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Http\FormRequest;

class AccountRequest extends FormRequest
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
            'create' => $this->getCreateRules(),
            'update' => $this->getUpdateRules(),
            'remove' => $this->getRemoveRules(),
            default => []
        };
    }

    public function messages(): array
    {
        return match (request()->route()->getActionMethod()) {
            'create' => $this->getCreateMessages(),
            'update' => $this->getUpdateMessages(),
            'remove' => $this->getRemoveMessages(),
            default => []
        };
    }

    public function formData(): array
    {
        return [
            'name'   => $this->input('name'),
            'remark' => $this->input('remark')
        ];
    }

    private function getCreateRules(): array
    {
        return [
            'name' => 'required|unique:accounts'
        ];
    }

    private function getCreateMessages(): array
    {
        return [
            'name.required' => '收款账户不能为空！',
            'name.unique'   => '收款账户已存在！'
        ];
    }

    private function getUpdateRules(): array
    {
        return [
            'id' => 'required|exists:accounts|not_in:1,2'
        ];
    }

    private function getUpdateMessages(): array
    {
        return [
            'id.required' => '缺少id参数！',
            'id.exists'   => '没有找到收款账户信息',
            'id.not_in'   => '系统保留,无法操作!'
        ];
    }

    private function getRemoveRules(): array
    {
        return [
            'id' => [
                'required',
                'not_in:1,2',
                'exists:accounts',
                function ($attribute, $value, $fail) {
                    if (DB::table('cashier_pay')->where('accounts_id', $value)->count()) {
                        $fail('[收费记录]已使用无法删除!');
                    }
                }
            ]
        ];
    }

    private function getRemoveMessages(): array
    {
        return [
            'id.required' => '缺少id参数！',
            'id.exists'   => '没有找到收款账户信息',
            'id.not_in'   => '系统保留,无法操作!'
        ];
    }
}
