<?php

namespace App\Http\Requests\PrescriptionFrequency;

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
            'name' => 'required|unique:prescription_frequency'
        ];
    }

    public function messages()
    {
        return [
            'name.required' => '用药频次不能为空!',
            'name.unique'   => '名称重复!'
        ];
    }
}
