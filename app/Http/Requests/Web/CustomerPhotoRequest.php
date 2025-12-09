<?php

namespace App\Http\Requests\Web;

use Illuminate\Foundation\Http\FormRequest;

class CustomerPhotoRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return match (request()->route()->getActionMethod()) {
            'create' => $this->getCreateRules(),
            'update' => $this->getUpdateRules(),
            'remove' => $this->getRemoveRules(),
            default => []
        };
    }

    /**
     * Get the validation messages that apply to the request.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return match (request()->route()->getActionMethod()) {
            'update' => $this->getUpdateMessages(),
            'remove' => $this->getRemoveMessages(),
            default => []
        };
    }

    /**
     * 创建相册的验证规则
     *
     * @return array
     */
    private function getCreateRules(): array
    {
        return [
            'customer_id' => 'required|exists:customer,id',
            'title'       => 'required',
            'flag'        => 'required',
        ];
    }

    /**
     * 更新相册的验证规则
     *
     * @return array
     */
    private function getUpdateRules(): array
    {
        return [
            'id'    => 'required|exists:customer_photos',
            'title' => 'required|string|max:255',
            'flag'  => 'required',
        ];
    }

    /**
     * 删除相册的验证规则
     *
     * @return array
     */
    private function getRemoveRules(): array
    {
        return [
            'id' => 'required|exists:customer_photos,id'
        ];
    }

    /**
     * 更新相册的错误消息
     *
     * @return array
     */
    private function getUpdateMessages(): array
    {
        return [
            'id.required'    => '相册ID不能为空',
            'id.exists'      => '相册不存在',
            'title.required' => '相册标题不能为空',
            'title.string'   => '相册标题必须为字符串',
            'title.max'      => '相册标题最大长度为255',
        ];
    }

    /**
     * 删除相册的错误消息
     *
     * @return array
     */
    private function getRemoveMessages(): array
    {
        return [
            'id.required' => '相册ID不能为空',
            'id.exists'   => '相册不存在'
        ];
    }

    /**
     * 创建相册的表单数据
     *
     * @return array
     */
    public function formData(): array
    {
        return match (request()->route()->getActionMethod()) {
            'create' => [
                'customer_id'    => $this->input('customer_id'),
                'title'          => $this->input('title'),
                'flag'           => $this->input('flag'),
                'remark'         => $this->input('remark'),
                'create_user_id' => user()->id,
            ],
            'update' => [
                'title'          => $this->input('title'),
                'flag'           => $this->input('flag'),
                'remark'         => $this->input('remark'),
                'create_user_id' => user()->id,
            ],
            default => []
        };
    }
}
