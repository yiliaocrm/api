<?php

namespace App\Http\Requests\Diagnosis;

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
            'category_id' => 'required|exists:diagnosis_category,id',
            'name'        => 'required'
        ];
    }

    public function messages()
    {
        return [
            'category_id.required' => '[诊断分类]不能为空!',
            'category_id.exists'   => '[诊断分类]找不到!',
            'name.required'        => '[诊断名称]不能为空!',
        ];
    }
}
