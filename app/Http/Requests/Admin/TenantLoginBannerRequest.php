<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class TenantLoginBannerRequest extends FormRequest
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
            'index' => $this->getIndexRules(),
            'create' => $this->getCreateRules(),
            'update' => $this->getUpdateRules(),
            'remove', 'info' => $this->getInfoRules(),
            'toggle' => $this->getToggleRules(),
            default => []
        };
    }

    public function messages(): array
    {
        return match (request()->route()->getActionMethod()) {
            'index' => $this->getIndexMessages(),
            'create' => $this->getCreateMessages(),
            'update' => $this->getUpdateMessages(),
            'remove', 'info' => $this->getInfoMessages(),
            'toggle' => $this->getToggleMessages(),
            default => []
        };
    }

    private function getIndexRules(): array
    {
        return [
            'keyword' => 'nullable|string|max:255',
        ];
    }

    private function getIndexMessages(): array
    {
        return [
            'keyword.string' => '[关键字]格式错误!',
            'keyword.max' => '[关键字]长度不能超过255个字符!',
        ];
    }

    private function getCreateRules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'image_path' => 'required|string',
            'link_url' => 'nullable|url',
            'order' => 'required|integer|min:0',
        ];
    }

    private function getCreateMessages(): array
    {
        return [
            'title.required' => '[Banner标题]不能为空!',
            'title.string' => '[Banner标题]格式错误!',
            'title.max' => '[Banner标题]长度不能超过255个字符!',
            'image_path.required' => '[图片]不能为空!',
            'image_path.string' => '[图片路径]格式错误!',
            'link_url.url' => '[跳转链接]格式错误!',
            'order.required' => '[排序权重]不能为空!',
            'order.integer' => '[排序权重]必须为整数!',
            'order.min' => '[排序权重]不能小于0!',
        ];
    }

    private function getUpdateRules(): array
    {
        return [
            'id' => 'required|exists:tenant_login_banners,id',
            'title' => 'required|string|max:255',
            'image_path' => 'required|string',
            'link_url' => 'nullable|url',
            'order' => 'required|integer|min:0',
        ];
    }

    private function getUpdateMessages(): array
    {
        return [
            'id.required' => '[Banner ID]不能为空!',
            'id.exists' => '[Banner]不存在!',
            'title.required' => '[Banner标题]不能为空!',
            'title.string' => '[Banner标题]格式错误!',
            'title.max' => '[Banner标题]长度不能超过255个字符!',
            'image_path.required' => '[图片]不能为空!',
            'image_path.string' => '[图片路径]格式错误!',
            'link_url.url' => '[跳转链接]格式错误!',
            'order.required' => '[排序权重]不能为空!',
            'order.integer' => '[排序权重]必须为整数!',
            'order.min' => '[排序权重]不能小于0!',
        ];
    }

    private function getInfoRules(): array
    {
        return [
            'id' => 'required|exists:tenant_login_banners,id'
        ];
    }

    private function getInfoMessages(): array
    {
        return [
            'id.required' => '[Banner ID]不能为空!',
            'id.exists' => '[Banner]不存在!'
        ];
    }

    private function getToggleRules(): array
    {
        return [
            'id' => 'required|exists:tenant_login_banners,id'
        ];
    }

    private function getToggleMessages(): array
    {
        return [
            'id.required' => '[Banner ID]不能为空!',
            'id.exists' => '[Banner]不存在!'
        ];
    }

    /**
     * 构造表单数据
     * @return array
     */
    public function formData(): array
    {
        return [
            'title'      => $this->input('title'),
            'image_path' => $this->input('image_path'),
            'link_url'   => $this->input('link_url'),
            'order'      => $this->input('order'),
            'disabled'   => $this->input('disabled', false),
        ];
    }
}
