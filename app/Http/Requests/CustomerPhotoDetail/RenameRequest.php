<?php

namespace App\Http\Requests\CustomerPhotoDetail;

use Illuminate\Foundation\Http\FormRequest;

class RenameRequest extends FormRequest
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
            'id'   => 'required|exists:customer_photo_details',
            'name' => 'required|string|max:255'
        ];
    }

    public function messages(): array
    {
        return [
            'id.required'   => '照片ID不能为空',
            'id.exists'     => '照片不存在',
            'name.required' => '照片名称不能为空',
            'name.string'   => '照片名称必须为字符串',
            'name.max'      => '照片名称最大长度为255'
        ];
    }
}
