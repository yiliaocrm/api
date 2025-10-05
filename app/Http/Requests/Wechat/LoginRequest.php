<?php

namespace App\Http\Requests\Wechat;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
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
     * @return array
     */
    public function rules(): array
    {
        return [
            'login_code' => 'required|string',
            'phone_code' => 'required|string',
        ];
    }

    public function messages(): array
    {
        return [
            'login_code.required' => 'login_code不能为空',
            'login_code.string'   => 'login_code必须是字符串',
            'phone_code.required' => 'phone_code不能为空',
            'phone_code.string'   => 'phone_code必须是字符串',
        ];
    }
}
