<?php

namespace App\Http\Requests\DataMaintenance;

use Illuminate\Foundation\Http\FormRequest;

class RemoveCustomerProductRequest extends FormRequest
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
            'id' => 'required|exists:customer_product'
        ];
    }

    public function messages(): array
    {
        return [
            'id.required' => 'id参数不能为空!',
            'id.exists'   => '数据不存在!'
        ];
    }
}
