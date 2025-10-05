<?php

namespace App\Http\Requests\Web;

use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Http\FormRequest;

class UnitRequest extends FormRequest
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
            default => []
        };
    }

    private function getCreateRules(): array
    {
        return [
            'name' => 'required|unique:unit'
        ];
    }

    private function getUpdateRules(): array
    {
        return [
            'id'   => 'required|integer|exists:unit',
            'name' => [
                'required',
                'string',
                Rule::unique('unit')->ignore($this->input('id'))
            ]
        ];
    }

    private function getRemoveRules(): array
    {
        return [
            'id' => [
                'required',
                'integer',
                'exists:unit',
                function ($attribute, $value, $fail) {
                    if (DB::table('goods_unit')->where('unit_id', $value)->count()) {
                        $fail('计量单位已经在使用中，无法删除！');
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
            'remove' => $this->getRemoveMessages(),
            default => []
        };
    }

    private function getCreateMessages(): array
    {
        return [
            'name.required' => '计量单位不能为空！',
            'name.unique'   => '计量单位已存在！'
        ];
    }

    private function getUpdateMessages(): array
    {
        return [
            'id.required'   => '缺少id参数！',
            'id.exists'     => '没有找到计量单位',
            'name.required' => '计量单位不能为空！',
            'name.unique'   => '计量单位已存在！'
        ];
    }

    private function getRemoveMessages(): array
    {
        return [
            'id.required' => '缺少id参数！',
            'id.exists'   => '没有找到计量单位',
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
