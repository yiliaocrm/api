<?php

namespace App\Http\Requests\DiagnosisCategory;

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
            'name'     => 'required',
            'parentid' => 'required|exists:diagnosis_category,id'
        ];
    }

    public function messages()
    {
        return [
            'name.required'     => '[分类名称]不能为空!',
            'parentid.required' => '[父节点]不能为空!',
            'parentid.exists'   => '[父节点]不存在!',
        ];
    }
}
