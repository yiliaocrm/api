<?php

namespace App\Http\Requests\Web;

use App\Models\Purchase;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class PurchaseTypeRequest extends FormRequest
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
            'info' => $this->getInfoRules(),
            'update' => $this->getUpdateRules(),
            'remove' => $this->getRemoveRules(),
            default => []
        };
    }

    private function getCreateRules(): array
    {
        return [
            'name' => 'required|unique:purchase_type,name'
        ];
    }

    private function getInfoRules(): array
    {
        return [
            'id' => 'required|exists:purchase_type'
        ];
    }

    private function getUpdateRules(): array
    {
        return [
            'id'   => 'required|exists:purchase_type',
            'name' => [
                'required',
                Rule::unique('purchase_type')->ignore($this->input('id'))
            ]
        ];
    }

    private function getRemoveRules(): array
    {
        return [
            'id' => [
                'required',
                'exists:purchase_type',
                function ($attribute, $value, $fail) {
                    if (Purchase::query()->where('type_id', $value)->first()) {
                        $fail('入库类别已经被使用，无法直接删除！');
                    }
                }
            ]
        ];
    }

    public function messages(): array
    {
        return match (request()->route()->getActionMethod()) {
            'create' => $this->getCreateMessages(),
            'info' => $this->getInfoMessages(),
            'update' => $this->getUpdateMessages(),
            'remove' => $this->getRemoveMessages(),
            default => []
        };
    }

    private function getCreateMessages(): array
    {
        return [
            'name.required' => '缺少name参数',
            'name.unique'   => "【{$this->input('name')}】已经存在！"
        ];
    }

    private function getInfoMessages(): array
    {
        return [
            'id.required' => 'id参数不能为空!',
            'id.exists'   => '数据不存在!'
        ];
    }

    private function getUpdateMessages(): array
    {
        return [
            'id.required'   => '缺少id参数！',
            'id.exists'     => '没有找到类别',
            'name.required' => '缺少name参数',
            'name.unique'   => "【{$this->input('name')}】已经存在！"
        ];
    }

    private function getRemoveMessages(): array
    {
        return [
            'id.required' => '缺少id参数！',
            'id.exists'   => '没有找到类别'
        ];
    }

    /**
     * 表单数据
     * @return array
     */
    public function formData(): array
    {
        return [
            'name' => $this->input('name')
        ];
    }
}
