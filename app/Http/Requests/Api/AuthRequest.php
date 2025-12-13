<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class AuthRequest extends FormRequest
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
            'login' => $this->getLoginRules(),
            'qrcode' => $this->getQrcodeRules(),
            default => []
        };
    }

    /**
     * 登录验证规则
     */
    private function getLoginRules(): array
    {
        return [
            'email'    => 'required|string',
            'password' => 'required|string',
        ];
    }

    /**
     * 扫码登录验证规则
     */
    private function getQrcodeRules(): array
    {
        return [
            'uuid' => [
                'required',
                function ($attribute, $uuid, $fail) {
                    if (!cache("qrcode.login.{$uuid}")) {
                        $fail('二维码不存在或者已过期,请重新获取!');
                    }
                }
            ]
        ];
    }
}
