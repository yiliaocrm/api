<?php

namespace App\Http\Requests\Web;

use Illuminate\Foundation\Http\FormRequest;

class PrescriptionFrequencyRequest extends FormRequest
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

    private function getCreateRules(): array
    {
        return [
            'name' => 'required|unique:prescription_frequency'
        ];
    }

    private function getUpdateRules(): array
    {
        return [
            'id'   => 'required|exists:prescription_frequency',
            'name' => 'unique:prescription_frequency,name,' . $this->input('id')
        ];
    }

    private function getRemoveRules(): array
    {
        return [
            'id' => 'required|exists:prescription_frequency'
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
            'name.required' => '用药频次不能为空!',
            'name.unique'   => '名称重复!'
        ];
    }

    private function getUpdateMessages(): array
    {
        return [
            'id.required' => 'id参数不能为空!',
            'id.exists'   => '没有找到数据!',
            'name.unique' => "《{$this->input('name')}》已存在"
        ];
    }

    private function getRemoveMessages(): array
    {
        return [
            'id.required' => '缺少id参数!',
        ];
    }
}
