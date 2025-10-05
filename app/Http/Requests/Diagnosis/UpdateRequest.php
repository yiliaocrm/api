<?php

namespace App\Http\Requests\Diagnosis;

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
    public function rules()
    {
        return [
            'id'          => 'required|exists:diagnosis',
            'name'        => 'required',
            'category_id' => 'required|exists:diagnosis_category,id',
        ];
    }

    public function messages()
    {
        return [
            'id.required'          => '缺少id参数',
            'id.exists'            => '没有找到数据',
            'name.required'        => '[诊断名称]不能为空',
            'category_id.required' => '[诊断分类]不能为空',
            'category_id.exists'   => '[诊断分类]不存在'
        ];
    }
}
