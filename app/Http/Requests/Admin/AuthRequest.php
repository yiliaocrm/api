<?php

namespace App\Http\Requests\Admin;

use Illuminate\Support\Facades\Hash;
use App\Rules\GoogleAuthenticatorRule;
use Illuminate\Foundation\Http\FormRequest;

class AuthRequest extends FormRequest
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

    public function rules(): array
    {
        return match (request()->route()->getActionMethod()) {
            'login' => $this->getLoginRules(),
            'resetPassword' => $this->getResetPasswordRules(),
            default => []
        };
    }

    public function messages(): array
    {
        return match (request()->route()->getActionMethod()) {
            'login' => $this->getLoginMessages(),
            'resetPassword' => $this->getResetPasswordMessages(),
            default => []
        };
    }

    private function getLoginRules(): array
    {
        $rules = [
            'username' => 'required|string|max:255',
            'password' => 'required|string|max:255',
        ];

        // 开启双重验证
        if (admin_parameter('central_login_tfa')) {
            $rules['code'] = [
                'required',
                'string',
                'max:6',
                new GoogleAuthenticatorRule(admin_parameter('tfa_secret'))
            ];
        }

        return $rules;
    }

    private function getLoginMessages(): array
    {
        return [
            'username.required' => '请输入邮箱',
            'username.string'   => '邮箱格式不正确',
            'username.max'      => '邮箱长度不能超过255个字符',
            'password.required' => '请输入密码',
            'password.string'   => '密码格式不正确',
            'password.max'      => '密码长度不能超过255个字符',
            'code.required'     => '请输入验证码',
            'code.string'       => '验证码格式不正确',
            'code.max'          => '验证码长度不能超过6个字符',
        ];
    }

    private function getResetPasswordRules(): array
    {
        return [
            'password' => 'required|confirmed|required_with:old',
            'old'      => [
                'required',
                function ($attribute, $value, $fail) {
                    if (!Hash::check($value, admin()->password)) {
                        $fail('旧密码输入错误！');
                    }
                }
            ]
        ];
    }

    private function getResetPasswordMessages(): array
    {
        return [
            'password.confirmed'     => '两次密码输入不一致！',
            'password.required_with' => '请输入新密码!',
            'old.required'           => '请输入旧密码!',
        ];
    }
}
