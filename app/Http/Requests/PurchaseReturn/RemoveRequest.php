<?php

namespace App\Http\Requests\PurchaseReturn;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class RemoveRequest extends FormRequest
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
    public function rules()
    {
        return [
            'id' => [
                'required',
                Rule::exists('purchase_return')->where('status', 1)
            ]
        ];
    }

    public function messages()
    {
        return [
            'id.required' => '缺少id参数',
            'id.exists'   => '状态错误,无法删除!',
        ];
    }
}
