<?php

namespace App\Http\Requests\Web;

use Illuminate\Foundation\Http\FormRequest;

class FieldRequest extends FormRequest
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
            'save' => [
                'page'   => 'required|string',
                'config' => 'required|array'
            ],
            'reset' => [
                'page' => 'required|string'
            ],
            default => []
        };
    }

    public function messages(): array
    {
        return match (request()->route()->getActionMethod()) {
            'save' => [
                'page.required'   => '页面标识不能为空',
                'page.string'     => '页面标识必须为字符串',
                'config.required' => '字段配置不能为空',
                'config.array'    => '字段配置必须为数组',
            ],
            'reset' => [
                'page.required' => '页面标识不能为空',
                'page.string'   => '页面标识必须为字符串',
            ],
            default => []
        };
    }
}
