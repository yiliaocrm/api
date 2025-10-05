<?php

namespace App\Http\Requests\PrescriptionWays;

use Illuminate\Foundation\Http\FormRequest;

class RemoveRequest extends FormRequest
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
            'id' => 'required|exists:prescription_ways'
        ];
    }

    public function messages()
    {
        return [
            'id.required' => '缺少id参数!',
        ];
    }
}
