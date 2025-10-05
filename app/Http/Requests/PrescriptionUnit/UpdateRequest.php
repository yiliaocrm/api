<?php

namespace App\Http\Requests\PrescriptionUnit;

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
            'id'   => 'required|exists:prescription_unit',
            'name' => 'unique:prescription_unit,name,' . $this->id . ',id'
        ];
    }

    public function messages()
    {
        return [
            'id.required' => 'id参数不能为空!',
            'id.exists'   => '没有找到数据!',
            'name.unique' => "《{$this->name}》已存在"
        ];
    }
}
