<?php

namespace App\Http\Requests\CustomerLevel;

use Illuminate\Foundation\Http\FormRequest;

class CreateRequest extends FormRequest
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
    public function rules()
    {
        return [
            'name' => 'required|unique:customer_level'
        ];
    }

    public function messages()
    {
        return [
            'name.required' => '请输入名称',
            'name.unique'   => "《{$this->name}》已存在！"
        ];
    }
}
