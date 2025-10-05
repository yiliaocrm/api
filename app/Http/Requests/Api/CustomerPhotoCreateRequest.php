<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class CustomerPhotoCreateRequest extends FormRequest
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
            'customer_id' => 'required|exists:customer,id',
            'title'       => 'required|string|max:255',
            'flag'        => 'required',
            'remark'      => 'nullable|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'customer_id.required' => '客户ID不能为空',
            'customer_id.exists'   => '客户不存在',
            'title.required'       => '标题不能为空',
            'title.string'         => '标题必须为字符串',
            'title.max'            => '标题最大长度为255',
            'flag.required'        => '标识不能为空',
            'remark.string'        => '备注必须为字符串',
            'remark.max'           => '备注最大长度为255',
        ];
    }

    public function formData(): array
    {
        return [
            'customer_id'    => $this->input('customer_id'),
            'title'          => $this->input('title'),
            'flag'           => $this->input('flag'),
            'remark'         => $this->input('remark'),
            'create_user_id' => user()->id,
        ];
    }
}
