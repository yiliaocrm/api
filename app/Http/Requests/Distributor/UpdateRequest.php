<?php

namespace App\Http\Requests\Distributor;

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
            'id' => 'required|exists:distributor',
            'parentid' => [
                'required_unless:is_top,on',
                'exists:distributor,user_id',
                new \App\Rules\Distributor\UpdateRule($this->id, $this->parentid)
            ],
        ];
    }

    public function messages()
    {
        return [
            'parentid.exists' => '[上级分销]不存在!'
        ];
    }
}
