<?php

namespace App\Http\Requests\Web;

use Illuminate\Foundation\Http\FormRequest;

class PrescriptionUnitRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

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
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return match (request()->route()->getActionMethod()) {
            'create' => $this->getCreateMessages(),
            'update' => $this->getUpdateMessages(),
            'remove' => $this->getRemoveMessages(),
            default => []
        };
    }

    /**
     * 创建验证规则
     */
    private function getCreateRules(): array
    {
        return [
            'name' => 'required|unique:prescription_unit'
        ];
    }

    /**
     * 创建验证消息
     */
    private function getCreateMessages(): array
    {
        return [
            'name.required' => '名称不能为空!',
            'name.unique'   => '名称重复!'
        ];
    }

    /**
     * 更新验证规则
     */
    private function getUpdateRules(): array
    {
        return [
            'id'   => 'required|exists:prescription_unit',
            'name' => 'unique:prescription_unit,name,' . $this->id . ',id'
        ];
    }

    /**
     * 更新验证消息
     */
    private function getUpdateMessages(): array
    {
        return [
            'id.required' => 'id参数不能为空!',
            'id.exists'   => '没有找到数据!',
            'name.unique' => "《{$this->name}》已存在"
        ];
    }

    /**
     * 删除验证规则
     */
    private function getRemoveRules(): array
    {
        return [
            'id' => 'required|exists:prescription_unit'
        ];
    }

    /**
     * 删除验证消息
     */
    private function getRemoveMessages(): array
    {
        return [
            'id.required' => '缺少id参数!',
        ];
    }
}
