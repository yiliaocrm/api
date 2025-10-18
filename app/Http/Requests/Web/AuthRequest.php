<?php

namespace App\Http\Requests\Web;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;
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

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
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

    /**
     * 获取登录验证规则
     *
     * @return array
     */
    private function getLoginRules(): array
    {
        $code = $this->input('code');

        // 一键登录模式
        if ($code) {
            return [
                'code' => [
                    'required',
                    function ($attribute, $value, $fail) {
                        $userId = Cache::get('login_token_' . $value);
                        if (!$userId) {
                            $fail('一键登录链接已过期或无效！');
                        }
                    }
                ]
            ];
        }

        // 传统账号密码登录
        $rules = [
            'email'    => 'required',
            'password' => 'required'
        ];

        if (parameter('cywebos_force_enable_google_authenticator')) {
            $rules['tfa'] = 'required';
        }

        return $rules;
    }

    /**
     * 获取登录验证消息
     *
     * @return array
     */
    private function getLoginMessages(): array
    {
        return [
            'email.required'    => '账号不能为空！',
            'password.required' => '密码不能为空！',
            'tfa.required'      => '口令不能为空！',
        ];
    }

    /**
     * 获取重置密码验证规则
     *
     * @return array
     */
    private function getResetPasswordRules(): array
    {
        return [
            'password' => 'required|confirmed|required_with:old',
            'old'      => [
                'required',
                function ($attribute, $value, $fail) {
                    if (!Hash::check($value, user()->password)) {
                        $fail('旧密码输入错误！');
                    }
                }
            ]
        ];
    }

    /**
     * 获取重置密码验证消息
     *
     * @return array
     */
    private function getResetPasswordMessages(): array
    {
        return [
            'password.confirmed'     => '两次密码输入不一致！',
            'password.required_with' => '请输入新密码!'
        ];
    }

    /**
     * 获取登录用户
     *
     * @return User|null
     */
    public function getLoginUser(): ?User
    {
        $code = $this->input('code');

        // 一键登录模式
        if ($code) {
            $userId = Cache::pull('login_token_' . $code);
            if ($userId) {
                return User::query()->find($userId);
            }
            return null;
        }

        // 传统账号密码登录
        return User::query()->where('email', $this->input('email'))->first();
    }

    /**
     * 获取登录日志数据
     *
     * @return array
     */
    public function getLoginLogData(): array
    {
        return [
            'type'        => 1,
            'fingerprint' => $this->input('fingerprint'),
            'remark'      => $this->input('code') ? '一键登录' : null
        ];
    }
}
