<?php

namespace App\Http\Requests\Import;

use Illuminate\Foundation\Http\FormRequest;

class CreateTemplateRequest extends FormRequest
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
        $id = $this->route('id');
        return [
            'title'    => 'required',
            'template'  => 'required|extensions:csv,xlsx,xls',
            'chunk_size' => 'required|numeric',
            'use_import' => ['required', $id ? 'unique:import_templates,id,'.$id :'unique:import_templates'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => '模板标题必须填写',
            'template.required' => '模板文件必须上传',
            'template.ext' => '模板文件后缀必须是 xlsx, xls, csv',
            'chunk_size.required' => '分块读取数量必须填写',
            'chunk_size.numeric' => '分块读取数量必须为数字',
            'use_import.required' => '导入类必须填写',
            'use_import.unique' => '导入类已被其他模板使用'
        ];
    }
}
