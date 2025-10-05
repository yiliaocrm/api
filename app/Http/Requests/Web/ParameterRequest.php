<?php

namespace App\Http\Requests\Web;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ParameterRequest extends FormRequest
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
            'info' => $this->getInfoRules(),
            'update' => $this->getUpdateRules(),
            default => [],
        };
    }

    public function messages(): array
    {
        return match (request()->route()->getActionMethod()) {
            'info' => $this->getInfoMessages(),
            'update' => $this->getUpdateMessages(),
            default => [],
        };
    }

    private function getUpdateRules(): array
    {
        return [
            'config'         => 'required|array',
            'config.*.name'  => 'required|string|exists:parameters,name',
            'config.*.value' => 'present',
        ];
    }

    private function getUpdateMessages(): array
    {
        return [
            'config.required'        => '参数config不能为空',
            'config.array'           => '参数config必须是数组',
            'config.*.name.required' => '配置项的名称不能为空',
            'config.*.name.string'   => '配置项的名称必须是字符串',
            'config.*.name.exists'   => '配置项 :input 不存在',
            'config.*.value.present' => '配置项 :attribute 的值必须存在',
        ];
    }

    private function getInfoRules(): array
    {
        return [
            'key' => [
                'required',
                'string',
                Rule::in([
                    'customer_create_selection_ascription',
                    'customer_allow_modify_medium'
                ])
            ],
        ];
    }

    private function getInfoMessages(): array
    {
        return [
            'key.required' => '参数key不能为空',
            'key.string'   => '参数key必须是字符串',
            'key.in'       => '参数key不在允许的范围内',
        ];
    }
}
