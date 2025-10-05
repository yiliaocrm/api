<?php

namespace App\Http\Requests\Web;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class ConsultantRemarkTemplateRequest extends FormRequest
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

    private function getCreateRules(): array
    {
        return [
            'title'   => 'required',
            'content' => 'required'
        ];
    }

    private function getUpdateRules(): array
    {
        return [
            'id'      => 'required|exists:consultant_remark_templates',
            'title'   => [
                'required',
                Rule::unique('consultant_remark_templates')->ignore($this->input('id'))
            ],
            'content' => 'required'
        ];
    }

    private function getRemoveRules(): array
    {
        return [
            'id' => 'required|exists:consultant_remark_templates'
        ];
    }

    public function messages(): array
    {
        return match (request()->route()->getActionMethod()) {
            'create' => $this->getCreateMessages(),
            'update' => $this->getUpdateMessages(),
            'remove' => $this->getRemoveMessages(),
            default => []
        };
    }

    private function getCreateMessages(): array
    {
        return [
            'title.required'   => '模板标题不能为空!',
            'content.required' => '模板内容不能为空!',
        ];
    }

    private function getUpdateMessages(): array
    {
        return [
            'id.required'      => 'id不能为空!',
            'id.exists'        => '没有找到数据!',
            'title.required'   => '模板标题不能为空!',
            'title.unique'     => '模板标题已存在!',
            'content.required' => '模板内容不能为空!'
        ];
    }

    private function getRemoveMessages(): array
    {
        return [
            'id.required' => '缺少id参数!',
            'id.exists'   => '没有找到记录!'
        ];
    }

    /**
     * 表单数据
     * @return array
     */
    public function formData(): array
    {
        return match (request()->route()->getActionMethod()) {
            'create' => [
                'title'          => $this->input('title'),
                'content'        => $this->input('content'),
                'create_user_id' => user()->id
            ],
            'update' => [
                'title'   => $this->input('title'),
                'content' => $this->input('content')
            ],
            default => []
        };
    }
}
