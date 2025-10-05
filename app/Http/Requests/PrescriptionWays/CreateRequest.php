<?php

namespace App\Http\Requests\PrescriptionWays;

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
            'name' => 'required|unique:prescription_ways',
            'type' => 'required'
        ];
    }

    public function messages()
    {
        return [
            'name.required' => '用药途径不能为空!',
            'name.unique'   => '名称重复!',
            'type.required' => '类别不能为空!'
        ];
    }
}
