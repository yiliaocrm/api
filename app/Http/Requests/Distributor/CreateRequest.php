<?php

namespace App\Http\Requests\Distributor;

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
            'parentid' => [
                'required_unless:is_top,on',
                'exists:distributor,user_id',
            ],
            'user_id'  => 'required|exists:users,id|unique:distributor'
        ];
    }

    public function messages()
    {
        return [
            'parentid.exists'  => '[上级分销]不存在!',
            'user_id.required' => '[分销人员]不能为空!',
            'user_id.exists'   => '[分销人员]不存在!',
            'user_id.unique'   => '[分销人员]无法重复添加!',
        ];
    }
}
