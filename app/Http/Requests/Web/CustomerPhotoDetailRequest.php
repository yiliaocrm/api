<?php

namespace App\Http\Requests\Web;

use App\Models\CustomerPhotoDetail;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Storage;

class CustomerPhotoDetailRequest extends FormRequest
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
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return match (request()->route()->getActionMethod()) {
            'rename' => $this->getRenameRules(),
            'remove' => $this->getRemoveRules(),
            'download' => $this->getDownloadRules(),
            default => []
        };
    }

    public function messages(): array
    {
        return match (request()->route()->getActionMethod()) {
            'rename' => $this->getRenameMessages(),
            'remove' => $this->getRemoveMessages(),
            'download' => $this->getDownloadMessages(),
            default => []
        };
    }

    /**
     * 重命名表单数据
     */
    public function formData(): array
    {
        return [
            'name' => $this->input('name')
        ];
    }

    private function getRenameRules(): array
    {
        return [
            'id'   => 'required|exists:customer_photo_details',
            'name' => 'required|string|max:255'
        ];
    }

    private function getRenameMessages(): array
    {
        return [
            'id.required'   => '照片ID不能为空',
            'id.exists'     => '照片不存在',
            'name.required' => '照片名称不能为空',
            'name.string'   => '照片名称必须为字符串',
            'name.max'      => '照片名称最大长度为255'
        ];
    }

    private function getRemoveRules(): array
    {
        return [
            'id' => 'required|exists:customer_photo_details,id'
        ];
    }

    private function getRemoveMessages(): array
    {
        return [
            'id.required' => '参数错误',
            'id.exists'   => '没有找到数据记录'
        ];
    }

    private function getDownloadRules(): array
    {
        return [
            'id' => [
                'required',
                function ($attribute, $id, $fail) {
                    $media = CustomerPhotoDetail::query()->find($id);
                    if (!$media) {
                        $fail('没有找到数据记录');
                    }
                    $attributes = $media->getAttributes();
                    $exists     = Storage::disk(config('filesystems.default'))->exists($attributes['file_path']);
                    if (!$exists) {
                        $fail('文件不存在!');
                    }
                }
            ]
        ];
    }

    private function getDownloadMessages(): array
    {
        return [
            'id.required' => '参数错误'
        ];
    }
}
