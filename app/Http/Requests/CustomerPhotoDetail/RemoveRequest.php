<?php

namespace App\Http\Requests\CustomerPhotoDetail;

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
            'id' => 'required|exists:customer_photo_details,id'
        ];
    }

    public function messages(): array
    {
        return [
            'id.required' => '参数错误',
            'id.exists'   => '没有找到数据记录'
        ];
    }
}
