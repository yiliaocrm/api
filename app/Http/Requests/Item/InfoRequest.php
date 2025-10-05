<?php

namespace App\Http\Requests\Item;

use Illuminate\Foundation\Http\FormRequest;

class InfoRequest extends FormRequest
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
            'id' => 'required|exists:item'
        ];
    }

    public function messages()
    {
        return [
            'id.required' => '缺少id参数！',
            'id.exists'   => '没有找到数据'
        ];
    }
}
