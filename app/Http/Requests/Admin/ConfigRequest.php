<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ConfigRequest extends FormRequest
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
            'save' => $this->getSaveRules(),
            'verify' => $this->getVerifyRules(),
            default => [],
        };
    }

    public function messages(): array
    {
        return match (request()->route()->getActionMethod()) {
            'save' => $this->getSaveMessages(),
            'verify' => $this->getVerifyMessages(),
            default => [],
        };
    }

    private function getSaveRules(): array
    {
        return [
            'config'         => 'required|array',
            'config.*.name'  => 'required|string|exists:admin_parameters,name',
            'config.*.value' => 'present',
        ];
    }

    private function getSaveMessages(): array
    {
        return [
            'config.required'        => '配置参数不能为空',
            'config.array'           => '配置参数格式错误',
            'config.*.name.required' => '参数名称不能为空',
            'config.*.name.string'   => '参数名称必须是字符串',
            'config.*.name.exists'   => '参数名称 :input 不存在',
        ];
    }

    private function getVerifyRules(): array
    {
        return [
            'secret' => 'required|string|size:32',
            'code'   => 'required|string|size:6',
        ];
    }

    private function getVerifyMessages(): array
    {
        return [
            'secret.required' => '密钥不能为空',
            'secret.string'   => '密钥格式错误',
            'secret.size'     => '密钥长度必须为32位',
            'code.required'   => '验证码不能为空',
            'code.string'     => '验证码格式错误',
            'code.size'       => '验证码必须为6位',
        ];
    }
}
