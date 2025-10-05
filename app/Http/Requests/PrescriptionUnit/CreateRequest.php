<?php

namespace App\Http\Requests\PrescriptionUnit;

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
            'name' => 'required|unique:prescription_unit'
        ];
    }

    public function messages()
    {
        return [
            'name.required' => '名称不能为空!',
            'name.unique'   => '名称重复!'
        ];
    }
}
