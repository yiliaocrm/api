<?php

namespace App\Http\Requests\Web;

use Illuminate\Foundation\Http\FormRequest;

class CacheRequest extends FormRequest
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
            default => [],
            'customerGroup' => $this->getCustomerGroupRules(),
        };
    }

    public function messages(): array
    {
        return match (request()->route()->getActionMethod()) {
            default => [],
            'customerGroup' => $this->getCustomerGroupMessages(),
        };
    }

    private function getCustomerGroupRules(): array
    {
        return [
            'type'     => 'nullable|string|in:dynamic,static,sql',
            'cascader' => 'nullable|string|in:true,false',
        ];
    }

    private function getCustomerGroupMessages(): array
    {
        return [
            'type.string'     => 'type类型必须是字符串',
            'type.in'         => 'type类型必须是 dynamic, static 或 sql',
            'cascader.string' => 'cascader必须是字符串',
            'cascader.in'     => 'cascader必须是 true 或 false',
        ];
    }
}
