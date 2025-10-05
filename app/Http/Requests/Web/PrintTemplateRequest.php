<?php

namespace App\Http\Requests\Web;

use Illuminate\Foundation\Http\FormRequest;

class PrintTemplateRequest extends FormRequest
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
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return match (request()->route()->getActionMethod()) {
            'create' => $this->getCreateRules(),
            'update' => $this->getUpdateRules(),
            'remove' => $this->getRemoveRules(),
            'info' => $this->getInfoRules(),
            'default' => $this->getDefaultRules(),
            'copy' => $this->getCopyRules(),
            default => []
        };
    }

    private function getCreateRules(): array
    {
        return [
            'name' => 'required',
            'type' => 'required',
        ];
    }

    private function getUpdateRules(): array
    {
        return [
            'id'      => 'required|exists:print_template',
            'name'    => 'required',
            'content' => 'required'
        ];
    }

    private function getRemoveRules(): array
    {
        return [
            'id' => 'required|exists:print_template'
        ];
    }

    private function getInfoRules(): array
    {
        return [
            'id' => 'required|exists:print_template,id'
        ];
    }

    private function getDefaultRules(): array
    {
        return [
            'id'      => 'required|exists:print_template,id',
            'default' => 'boolean'
        ];
    }

    private function getCopyRules(): array
    {
        return [
            'id'   => 'required|exists:print_template,id',
            'name' => 'required|string|max:255'
        ];
    }

    public function messages(): array
    {
        return match (request()->route()->getActionMethod()) {
            'create' => $this->getCreateMessages(),
            'update' => $this->getUpdateMessages(),
            'remove' => $this->getRemoveMessages(),
            'info' => $this->getInfoMessages(),
            'default' => $this->getDefaultMessages(),
            'copy' => $this->getCopyMessages(),
            default => []
        };
    }

    private function getCreateMessages(): array
    {
        return [
            'name.required' => '[模板名称]不能为空!',
            'type.required' => '[模板类型]不能为空!',
        ];
    }

    private function getUpdateMessages(): array
    {
        return [
            'id.required'      => '缺少id参数',
            'id.exists'        => '模板不存在',
            'name.required'    => '请输入模板名称',
            'content.required' => '模板标签不能为空!',
        ];
    }

    private function getRemoveMessages(): array
    {
        return [
            'id.required' => '不能缺少id参数',
            'id.exists'   => '找不到打印模板'
        ];
    }

    private function getInfoMessages(): array
    {
        return [
            'id.required' => '模板id不能为空'
        ];
    }

    private function getDefaultMessages(): array
    {
        return [
            'id.required'     => '模板id不能为空',
            'id.exists'       => '模板不存在',
            'default.boolean' => '默认状态必须是布尔值'
        ];
    }

    private function getCopyMessages(): array
    {
        return [
            'id.required'   => '模板id不能为空',
            'id.exists'     => '模板不存在',
            'name.required' => '新模板名称不能为空',
            'name.string'   => '模板名称必须是字符串',
            'name.max'      => '模板名称不能超过255个字符'
        ];
    }

    /**
     * 表单数据
     * @return array
     */
    public function fillData(): array
    {
        return match (request()->route()->getActionMethod()) {
            'create' => $this->getCreateData(),
            'update' => $this->getUpdateData(),
            default => []
        };
    }

    private function getCreateData(): array
    {
        return [
            'name'    => $this->input('name'),
            'type'    => $this->input('type'),
            'content' => null
        ];
    }

    private function getUpdateData(): array
    {
        return [
            'name'    => $this->input('name'),
            'content' => $this->input('content')
        ];
    }
}
