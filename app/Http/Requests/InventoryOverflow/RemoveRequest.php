<?php

namespace App\Http\Requests\InventoryOverflow;

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
    public function rules(): array
    {
        return [
            'id' => 'required|exists:inventory_overflows,id,status,1'
        ];
    }

    public function messages()
    {
        return [
            'id.required' => 'id参数不能为空!',
            'id.exists'   => '数据不存在,或者单据状态错误!'
        ];
    }
}
