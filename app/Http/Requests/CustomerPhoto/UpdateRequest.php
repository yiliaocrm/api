<?php

namespace App\Http\Requests\CustomerPhoto;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRequest extends FormRequest
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
            'id'    => 'required|exists:customer_photos',
            'title' => 'required|string|max:255',
            'flag'  => 'required',
        ];
    }

    public function messages(): array
    {
        return [
            'id.required'    => '相册ID不能为空',
            'id.exists'      => '相册不存在',
            'title.required' => '相册标题不能为空',
            'title.string'   => '相册标题必须为字符串',
            'title.max'      => '相册标题最大长度为255',
        ];
    }

    public function formData(): array
    {
        return [
            'title'          => $this->input('title'),
            'flag'           => $this->input('flag'),
            'remark'         => $this->input('remark'),
            'create_user_id' => user()->id,
        ];
    }
}
