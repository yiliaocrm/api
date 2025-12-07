<?php

namespace App\Http\Requests\Web;

use Illuminate\Foundation\Http\FormRequest;

class AttachmentRequest extends FormRequest
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
            'check' => $this->getCheckRules(),
            'upload' => $this->getUploadRules(),
            'remove' => $this->getRemoveRules(),
            'createGroup' => $this->getCreateGroupRules(),
            'updateGroup' => $this->getUpdateGroupRules(),
            'removeGroup' => $this->getRemoveGroupRules(),
            default => []
        };
    }

    public function messages(): array
    {
        return match (request()->route()->getActionMethod()) {
            'check' => $this->getCheckMessages(),
            'upload' => $this->getUploadMessages(),
            'remove' => $this->getRemoveMessages(),
            'createGroup' => $this->getCreateGroupMessages(),
            'updateGroup' => $this->getUpdateGroupMessages(),
            'removeGroup' => $this->getRemoveGroupMessages(),
            default => []
        };
    }

    private function getCheckRules(): array
    {
        return [
            'md5' => 'required|string|size:32',
        ];
    }

    private function getCheckMessages(): array
    {
        return [
            'md5.required' => '文件MD5值不能为空',
            'md5.string'   => '文件MD5值必须是字符串',
            'md5.size'     => '文件MD5值长度必须为32位',
        ];
    }

    private function getUploadRules(): array
    {
        return [
            'file' => 'required|file',
        ];
    }

    private function getUploadMessages(): array
    {
        return [
            'file.required' => '请选择上传文件',
            'file.file'     => '上传内容必须是文件',
            'md5.string'    => '文件MD5值必须是字符串',
            'md5.size'      => '文件MD5值长度必须为32位',
        ];
    }

    private function getRemoveRules(): array
    {
        return [
            'id' => 'required|integer|exists:attachments,id',
        ];
    }

    private function getRemoveMessages(): array
    {
        return [
            'id.required' => '附件ID不能为空',
            'id.integer'  => '附件ID必须是整数',
            'id.exists'   => '附件不存在',
        ];
    }

    private function getCreateGroupRules(): array
    {
        return [
            'name'      => 'required|string|max:50',
            'parent_id' => 'nullable|exists:attachment_groups,id',
            'order'     => 'nullable|integer',
        ];
    }

    private function getCreateGroupMessages(): array
    {
        return [
            'name.required'    => '分组名称不能为空',
            'name.string'      => '分组名称必须是字符串',
            'name.max'         => '分组名称最多50个字符',
            'parent_id.exists' => '父分组不存在',
            'order.integer'    => '排序必须是整数',
        ];
    }

    private function getUpdateGroupRules(): array
    {
        return [
            'id'        => 'required|exists:attachment_groups,id',
            'name'      => 'required|string|max:50',
            'parent_id' => 'nullable|exists:attachment_groups,id',
            'order'     => 'nullable|integer',
        ];
    }

    private function getUpdateGroupMessages(): array
    {
        return [
            'id.required'      => '分组ID不能为空',
            'id.exists'        => '分组不存在',
            'name.required'    => '分组名称不能为空',
            'name.string'      => '分组名称必须是字符串',
            'name.max'         => '分组名称最多50个字符',
            'parent_id.exists' => '父分组不存在',
            'order.integer'    => '排序必须是整数',
        ];
    }

    private function getRemoveGroupRules(): array
    {
        return [
            'id' => 'required|exists:attachment_groups,id',
        ];
    }

    private function getRemoveGroupMessages(): array
    {
        return [
            'id.required' => '分组ID不能为空',
            'id.exists'   => '分组不存在',
        ];
    }
}
