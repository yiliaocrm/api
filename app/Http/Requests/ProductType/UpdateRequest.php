<?php

namespace App\Http\Requests\ProductType;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
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
            'id'   => 'required|exists:product_type',
            'name' => 'required'
        ];
    }

    /**
     * 表单数据
     * @return array
     */
    public function formData(): array
    {
        return [
            'name' => $this->input('name')
        ];
    }
}
