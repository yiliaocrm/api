<?php

namespace App\Http\Requests\Miniapp;

use Illuminate\Foundation\Http\FormRequest;

class ChangeRequest extends FormRequest
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
        return [
            'id'          => 'required|integer|exists:customer_wechats,id',
            'customer_id' => 'required|exists:customer,id',
        ];
    }

    public function messages(): array
    {
        return [
            'id.required'          => '小程序ID不能为空',
            'id.integer'           => '小程序ID必须为整数',
            'id.exists'            => '小程序ID不存在',
            'customer_id.required' => '顾客ID不能为空',
            'customer_id.exists'   => '顾客ID不存在',
        ];
    }
}
