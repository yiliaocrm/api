<?php

namespace App\Http\Requests\Erkai;

use Illuminate\Foundation\Http\FormRequest;

class InfoRequest extends FormRequest
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
        return [
            'id' => 'required|exists:erkai'
        ];
    }

    public function messages(): array
    {
        return [
            'id.required' => 'id不能为空!',
            'id.exists'   => '数据不存在!',
        ];
    }
}
