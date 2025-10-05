<?php

namespace App\Http\Requests\PrescriptionWays;

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
            'id'   => 'required|exists:prescription_ways',
            'name' => 'unique:prescription_ways,name,' . $this->id . ',id',
            'type' => 'required'
        ];
    }

    public function messages()
    {
        return [
            'id.required' => 'id参数不能为空!',
            'id.exists'   => '没有找到数据!',
            'name.unique' => "《{$this->name}》已存在",
            'type.unique' => '类别不能为空!',
        ];
    }
}
