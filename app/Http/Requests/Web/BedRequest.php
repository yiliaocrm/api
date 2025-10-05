<?php

namespace App\Http\Requests\Web;

use Illuminate\Foundation\Http\FormRequest;

class BedRequest extends FormRequest
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
            default => []
        };
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

    private function getCreateRules(): array
    {
        return [
            'name'   => 'required|string|max:255',
            'status' => 'required|integer|in:0,1,2,3',
            'remark' => 'nullable|string|max:255',
        ];
    }

    private function getCreateMessages(): array
    {
        return [
            'name.required'   => '床位名称不能为空！',
            'name.string'     => '床位名称必须是字符串！',
            'name.max'        => '床位名称不能超过255个字符！',
            'status.required' => '床位状态不能为空！',
            'status.integer'  => '床位状态必须是整数！',
            'status.in'       => '床位状态必须是0、1、2或3！',
            'remark.string'   => '备注必须是字符串！',
            'remark.max'      => '备注不能超过255个字符！',
            'remark.required' => '备注不能为空！',
        ];
    }


    private function getUpdateRules(): array
    {
        return [
            'id'     => 'required|integer|exists:bed',
            'name'   => 'required|string|max:255',
            'status' => 'required|integer|in:0,1,2,3',
            'remark' => 'nullable|string|max:255',
        ];
    }

    private function getUpdateMessages(): array
    {
        return [
            'id.required'     => '缺少id参数！',
            'id.exists'       => '没有找到床位',
            'name.required'   => '床位名称不能为空！',
            'name.string'     => '床位名称必须是字符串！',
            'name.max'        => '床位名称不能超过255个字符！',
            'status.required' => '床位状态不能为空！',
            'status.integer'  => '床位状态必须是整数！',
            'status.in'       => '床位状态必须是0、1、2或3！',
            'remark.string'   => '备注必须是字符串！',
            'remark.max'      => '备注不能超过255个字符！',
        ];
    }

    private function getRemoveRules(): array
    {
        return [
            'id' => 'required|integer|exists:bed'
        ];
    }

    private function getRemoveMessages(): array
    {
        return [
            'id.required' => '缺少id参数！',
            'id.exists'   => '没有找到床位'
        ];
    }

    /**
     * 表单数据
     * @return array
     */
    public function formData(): array
    {
        return [
            'name'     => $this->input('name'),
            'status'   => $this->input('status'),
            'remark'   => $this->input('remark'),
            'store_id' => 1
        ];
    }
}
