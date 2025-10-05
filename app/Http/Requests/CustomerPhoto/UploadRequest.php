<?php

namespace App\Http\Requests\CustomerPhoto;

use Illuminate\Foundation\Http\FormRequest;

class UploadRequest extends FormRequest
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
            'id'   => 'required|exists:customer_photos,id',
            'file' => 'required|image|max:10240'
        ];
    }

    public function messages(): array
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
     * 明细表信息
     * @param $album
     * @param $attachment
     * @param $thumbnail
     * @return array
     */
    public function formData($album, $attachment, $thumbnail): array
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
