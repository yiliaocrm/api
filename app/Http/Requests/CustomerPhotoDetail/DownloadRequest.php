<?php

namespace App\Http\Requests\CustomerPhotoDetail;

use App\Models\CustomerPhotoDetail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Http\FormRequest;

class DownloadRequest extends FormRequest
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

    public function messages(): array
    {
        return [
            'id.required' => '参数错误'
        ];
    }
}
