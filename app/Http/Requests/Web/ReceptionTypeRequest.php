<?php

namespace App\Http\Requests\Web;

use App\Models\Reception;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class ReceptionTypeRequest extends FormRequest
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
            'create' => $this->getCreateRules(),
            'update' => $this->getUpdateRules(),
            'info' => $this->getInfoRules(),
            'remove' => $this->getRemoveRules(),
            default => []
        };
    }

    private function getCreateRules(): array
    {
        return [
            'name' => 'required|unique:reception_type'
        ];
    }

    private function getUpdateRules(): array
    {
        return [
            'id'   => 'required|exists:reception_type',
            'name' => [
                'required',
                Rule::unique('reception_type')->ignore($this->input('id')),
            ]
        ];
    }

    private function getInfoRules(): array
    {
        return [
            'id' => 'required|exists:reception_type'
        ];
    }

    private function getRemoveRules(): array
    {
        return [
            'id' => [
                'required',
                'exists:reception_type',
                function ($attribute, $value, $fail) {
                    if (Reception::query()->where('type', $value)->first()) {
                        $fail('《分诊接待》表中已经使用,无法删除!');
                    }
                }
            ]
        ];
    }

    public function messages(): array
    {
        return match (request()->route()->getActionMethod()) {
            'create' => $this->getCreateMessages(),
            'update' => $this->getUpdateMessages(),
            'info' => $this->getInfoMessages(),
            'remove' => $this->getRemoveMessages(),
            default => []
        };
    }

    private function getCreateMessages(): array
    {
        return [
            'name.required' => '请输入类型名称',
            'name.unique'   => "《{$this->input('name')}》类型已存在！"
        ];
    }

    private function getUpdateMessages(): array
    {
        return [
            'id.required'   => '缺少id参数!',
            'id.exists'     => '没有找到id参数',
            'name.required' => '名称不能为空!',
            'name.unique'   => "《{$this->input('name')}》名称已存在!"
        ];
    }

    private function getInfoMessages(): array
    {
        return [
            'id.required' => '缺少id参数!',
            'id.exists'   => '没有找到数据!'
        ];
    }

    private function getRemoveMessages(): array
    {
        return [
            'id.required' => '缺少id参数!',
            'id.exists'   => '没有找到数据!'
        ];
    }
}
