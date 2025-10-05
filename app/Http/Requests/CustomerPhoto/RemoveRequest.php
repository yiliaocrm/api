<?php

namespace App\Http\Requests\CustomerPhoto;

use Illuminate\Foundation\Http\FormRequest;

class RemoveRequest extends FormRequest
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
            'id' => 'required|exists:customer_photos,id'
        ];
    }

    public function messages(): array
    {
        return [
            'id.required' => '相册ID不能为空',
            'id.exists'   => '相册不存在'
        ];
    }
}
