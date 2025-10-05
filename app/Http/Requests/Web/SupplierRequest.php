<?php

namespace App\Http\Requests\Web;

use App\Models\Purchase;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class SupplierRequest extends FormRequest
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
            'remove' => $this->getRemoveRules(),
            'info', 'enable', 'disable' => $this->getInfoRules(),
            default => []
        };
    }

    private function getCreateRules(): array
    {
        return [
            'name'       => 'required|unique:supplier',
            'short_name' => 'required',
        ];
    }

    private function getUpdateRules(): array
    {
        return [
            'id'         => 'required|exists:supplier',
            'name'       => [
                'required',
                'string',
                'max:255',
                Rule::unique('supplier')->ignore($this->input('id'))
            ],
            'short_name' => 'required|string|max:255',
        ];
    }

    private function getRemoveRules(): array
    {
        return [
            'id' => [
                'required',
                'exists:supplier',
                function ($attribute, $value, $fail) {
                    if (Purchase::query()->where('supplier_id', $value)->first()) {
                        $fail('【采购表】已经使用,无法删除!');
                    }
                }
            ]
        ];
    }

    private function getInfoRules(): array
    {
        return [
            'id' => 'required|exists:supplier'
        ];
    }

    public function messages(): array
    {
        return match (request()->route()->getActionMethod()) {
            'create' => $this->getCreateMessages(),
            'update' => $this->getUpdateMessages(),
            'remove' => $this->getRemoveMessages(),
            'info', 'enable', 'disable' => $this->getInfoMessages(),
            default => []
        };
    }

    private function getCreateMessages(): array
    {
        return [
            'name.required'       => '供应商名称不能为空！',
            'name.unique'         => '供应商已存在！',
            'short_name.required' => '供应商简称不能为空！',
        ];
    }

    private function getUpdateMessages(): array
    {
        return [
            'id.required'         => '缺少id参数！',
            'id.exists'           => '没有找到供应商信息',
            'name.required'       => '请输入供应商名称',
            'name.string'         => '供应商名称必须是字符串',
            'name.max'            => '供应商名称最大长度为255',
            'name.unique'         => '供应商名称已存在！',
            'short_name.required' => '请输入供应商简称',
            'short_name.string'   => '供应商简称必须是字符串',
            'short_name.max'      => '供应商简称最大长度为255',
        ];
    }

    private function getRemoveMessages(): array
    {
        return [
            'id.required' => '缺少id参数！',
            'id.exists'   => '没有找到供应商信息'
        ];
    }

    private function getInfoMessages(): array
    {
        return [
            'id.required' => '缺少id参数！',
            'id.exists'   => '没有找到供应商信息'
        ];
    }

    /**
     * 表单数据
     * @return array
     */
    public function formData(): array
    {
        return [
            'name'       => $this->input('name'),
            'short_name' => $this->input('short_name'),
            'remark'     => $this->input('remark')
        ];
    }
}
