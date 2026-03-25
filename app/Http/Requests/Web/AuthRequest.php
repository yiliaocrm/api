<?php

namespace App\Http\Requests\Web;

use App\Models\User;
use App\Rules\GoogleAuthenticatorRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AuthRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
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
            'updateProfile' => $this->getUpdateProfileRules(),
            'postSecret' => $this->getPostSecretRules(),
            'clearSecret' => $this->getClearSecretRules(),
            'loginLogs' => $this->getLoginLogsRules(),
            default => []
        };
    }

    public function messages(): array
    {
        return match (request()->route()->getActionMethod()) {
            'login' => $this->getLoginMessages(),
            'resetPassword' => $this->getResetPasswordMessages(),
            'updateProfile' => $this->getUpdateProfileMessages(),
            'postSecret' => $this->getPostSecretMessages(),
            'clearSecret' => $this->getClearSecretMessages(),
            'loginLogs' => $this->getLoginLogsMessages(),
            default => []
        };
    }

    /**
     * 获取登录验证规则
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
                        $userId = Cache::get('login_token_'.$value);
                        if (! $userId) {
                            $fail('一键登录链接已过期或无效！');
                        }
                    },
                ],
            ];
        }

        // 传统账号密码登录
        $rules = [
            'email' => 'required',
            'password' => 'required',
        ];

        if (parameter('cywebos_force_enable_google_authenticator')) {
            $rules['tfa'] = 'required';
        }

        return $rules;
    }

    /**
     * 获取登录验证消息
     */
    private function getLoginMessages(): array
    {
        return [
            'email.required' => '账号不能为空！',
            'password.required' => '密码不能为空！',
            'tfa.required' => '口令不能为空！',
        ];
    }

    /**
     * 获取重置密码验证规则
     */
    private function getResetPasswordRules(): array
    {
        return [
            'password' => 'required|confirmed|required_with:old',
            'old' => [
                'required',
                function ($attribute, $value, $fail) {
                    if (! Hash::check($value, user()->password)) {
                        $fail('旧密码输入错误！');
                    }
                },
            ],
        ];
    }

    /**
     * 获取重置密码验证消息
     */
    private function getResetPasswordMessages(): array
    {
        return [
            'password.confirmed' => '两次密码输入不一致！',
            'password.required_with' => '请输入新密码!',
        ];
    }

    /**
     * 获取个人资料更新验证规则
     */
    private function getUpdateProfileRules(): array
    {
        return [
            'name' => 'required',
            'avatar' => 'nullable|string',
            'extension' => [
                'nullable',
                Rule::unique('users', 'extension')->ignore(user()->id),
            ],
            'remark' => 'nullable|string',
        ];
    }

    /**
     * 获取绑定动态口令验证规则
     */
    private function getPostSecretRules(): array
    {
        return [
            'secret' => 'required',
            'code' => [
                'required',
                new GoogleAuthenticatorRule($this->input('secret')),
            ],
        ];
    }

    /**
     * 获取解绑动态口令验证规则
     */
    private function getClearSecretRules(): array
    {
        return [];
    }

    /**
     * 获取登录日志验证规则
     */
    private function getLoginLogsRules(): array
    {
        return [
            'rows' => 'nullable|integer',
            'page' => 'nullable|integer',
        ];
    }

    /**
     * 获取个人资料更新验证消息
     */
    private function getUpdateProfileMessages(): array
    {
        return [
            'name.required' => '姓名不能为空！',
            'extension.unique' => '分机号码已被使用!',
        ];
    }

    /**
     * 获取绑定动态口令验证消息
     */
    private function getPostSecretMessages(): array
    {
        return [
            'secret.required' => '缺少secret参数！',
            'code.required' => '动态口令不能为空!',
        ];
    }

    /**
     * 获取解绑动态口令验证消息
     */
    private function getClearSecretMessages(): array
    {
        return [];
    }

    /**
     * 获取登录日志验证消息
     */
    private function getLoginLogsMessages(): array
    {
        return [
            'rows.integer' => '每页条数必须为整数！',
            'page.integer' => '页码必须为整数！',
        ];
    }

    /**
     * 获取登录用户
     */
    public function getLoginUser(): ?User
    {
        $code = $this->input('code');

        // 一键登录模式
        if ($code) {
            $userId = Cache::pull('login_token_'.$code);
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
     */
    public function getLoginLogData(): array
    {
        return [
            'type' => 1,
            'fingerprint' => $this->input('fingerprint'),
            'remark' => $this->input('code') ? '一键登录' : null,
        ];
    }
}
