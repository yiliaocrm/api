<?php

namespace App\Http\Requests\Web;

use App\Rules\GoogleAuthenticatorRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserRequest extends FormRequest
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
            'edit' => $this->getEditRules(),
            'info', 'getPermission', 'postPermission', 'clearPermission', 'ban', 'unban', 'loginCode' => $this->getInfoRules(),
            'clearSecret' => $this->getClearSecretRules(),
            'postSecret' => $this->getUpdateSecretRules(),
            default => []
        };
    }

    public function messages(): array
    {
        return match (request()->route()->getActionMethod()) {
            'create' => $this->getCreateMessages(),
            'edit' => $this->getEditMessages(),
            'info', 'getPermission', 'postPermission', 'clearPermission', 'ban', 'unban', 'loginCode' => $this->getInfoMessages(),
            'clearSecret' => $this->getClearSecretMessages(),
            'postSecret' => $this->getUpdateSecretMessages(),
            default => []
        };
    }

    private function getCreateRules(): array
    {
        return [
            'email'         => 'required|regex:/^[a-zA-Z0-9_-]{4,16}$/|unique:users',
            'name'          => 'required',
            'password'      => 'required|confirmed',
            'department_id' => 'required',
            'scheduleable'  => 'required|boolean',
            'extension'     => 'nullable|unique:users'
        ];
    }

    private function getEditRules(): array
    {
        return [
            'id'            => 'required|exists:users',
            'name'          => 'required',
            'email'         => [
                Rule::unique('users')->ignore($this->input('id'))
            ],
            'password'      => 'confirmed',
            'department_id' => 'required|exists:department,id',
            'scheduleable'  => 'required|boolean',
            'extension'     => [
                'nullable',
                Rule::unique('users')->ignore($this->input('id'))
            ]
        ];
    }

    private function getInfoRules(): array
    {
        return [
            'id' => 'required|exists:users'
        ];
    }

    private function getClearSecretRules(): array
    {
        return [
            'id' => 'required|exists:users'
        ];
    }

    private function getUpdateSecretRules(): array
    {
        return [
            'id'     => 'required|exists:users',
            'secret' => 'required',
            'code'   => [
                'required',
                new GoogleAuthenticatorRule(request('secret'))
            ]
        ];
    }

    private function getCreateMessages(): array
    {
        return [
            'email.required'     => '登陆账号不能为空！',
            'email.regex'        => '登陆账号格式错误！',
            'email.unique'       => '登陆账号已存在!',
            'name.required'      => '真实姓名不能为空！',
            'password.required'  => '密码不能为空！',
            'password.confirmed' => '两次密码输入不一致！',
            'extension.unique'   => '分机号码已被使用!'
        ];
    }

    private function getEditMessages(): array
    {
        return [
            'id.required'        => '缺少id参数！',
            'email.unique'       => '登陆账号已存在!',
            'password.confirmed' => '两次密码输入不一致!',
            'extension.unique'   => '分机号码已被使用!'
        ];
    }

    private function getInfoMessages(): array
    {
        return [
            'id.required' => '缺少id参数!',
            'id.exists'   => '没有找到管理员'
        ];
    }

    private function getClearSecretMessages(): array
    {
        return [
            'id.required' => '缺少id参数!',
            'id.exists'   => '没有找到用户信息!'
        ];
    }

    private function getUpdateSecretMessages(): array
    {
        return [
            'id.required'     => '缺少id参数!',
            'id.exists'       => '没有找到用户信息!',
            'secret.required' => '缺少secret参数！',
            'code.required'   => '动态口令不能为空!'
        ];
    }

    /**
     * 表单数据
     * @return array
     */
    public function formData(): array
    {
        return match (request()->route()->getActionMethod()) {
            'create' => $this->getCreateFormData(),
            'edit' => $this->getEditFormData(),
            default => []
        };
    }

    private function getCreateFormData(): array
    {
        return [
            'email'         => $this->input('email'),
            'password'      => $this->input('password'),
            'name'          => $this->input('name'),
            'department_id' => $this->input('department_id'),
            'scheduleable'  => $this->input('scheduleable'),
            'extension'     => $this->input('extension'),
            'remark'        => $this->input('remark')
        ];
    }

    private function getEditFormData(): array
    {
        $data = [
            'email'                 => $this->input('email'),
            'name'                  => $this->input('name'),
            'password'              => $this->input('password'),
            'password_confirmation' => $this->input('password_confirmation'),
            'department_id'         => $this->input('department_id'),
            'scheduleable'          => $this->input('scheduleable'),
            'extension'             => $this->input('extension'),
            'remark'                => $this->input('remark'),
        ];

        // 没有修改密码
        if (empty($data['password'])) {
            unset($data['password']);
            unset($data['password_confirmation']);
        }

        return $data;
    }
}
