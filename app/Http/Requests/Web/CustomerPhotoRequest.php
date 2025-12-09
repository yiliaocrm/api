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
            'upload' => $this->getUploadRules(),
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
            'upload' => $this->getUploadMessages(),
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
     * 上传对比照的验证规则
     *
     * @return array
     */
    private function getUploadRules(): array
    {
        return [
            'id'   => 'required|exists:customer_photos,id',
            'file' => 'required|image|max:10240'
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
     * 上传对比照的错误消息
     *
     * @return array
     */
    private function getUploadMessages(): array
    {
        return [
            'id.required'   => '请选择相册后上传文件',
            'id.exists'     => '相册数据不存在',
            'file.required' => '请选择图片上传',
            'file.image'    => '只允许上传图片',
            'file.max'      => '文件大小不能超过10M',
        ];
    }

    /**
     * 创建相册的表单数据
     *
     * @return array
     */
    public function formData(...$args): array
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
            'upload' => $this->getUploadFormData(...$args),
            default => []
        };
    }

    /**
     * 上传对比照的表单数据
     *
     * @param $album
     * @param $attachment
     * @param $thumbnail
     * @return array
     */
    private function getUploadFormData($album, $attachment, $thumbnail): array
    {
        return [
            'customer_photo_id' => $album->id,
            'customer_id'       => $album->customer_id,
            'name'              => $attachment['file_name'],
            'thumb'             => $thumbnail['file_path'],
            'file_path'         => $attachment['file_path'],
            'file_mime'         => $attachment['file_mime'],
            'create_user_id'    => user()->id,
        ];
    }
}
