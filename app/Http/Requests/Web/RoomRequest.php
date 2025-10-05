<?php

namespace App\Http\Requests\Web;

use Illuminate\Foundation\Http\FormRequest;

class RoomRequest extends FormRequest
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
            'name'          => 'required|unique:room',
            'status'        => 'required|in:0,1,2,3',
            'remark'        => 'nullable|string|max:255',
            'department_id' => 'required|exists:department,id'
        ];
    }

    private function getCreateMessages(): array
    {
        return [
            'name.required'          => '[房间名称]不能为空',
            'name.unique'            => '[房间名称]重复',
            'department_id.required' => '[所属科室]不能为空!',
            'department_id.exists'   => '[所属科室]不能不存在!',
        ];
    }

    private function getUpdateRules(): array
    {
        return [
            'id'            => 'required|exists:room',
            'name'          => 'required',
            'department_id' => 'required|exists:department,id'
        ];
    }

    private function getUpdateMessages(): array
    {
        return [
            'id.required'            => '缺少id参数',
            'id.exists'              => '没有找到数据',
            'department_id.required' => '[所属科室]不能为空!',
            'department_id.exists'   => '[所属科室]不能不存在!',
        ];
    }

    private function getRemoveRules(): array
    {
        return [
            'id' => 'required|exists:room'
        ];
    }

    private function getRemoveMessages(): array
    {
        return [
            'id.required' => '缺少id参数',
            'id.exists'   => '没有找到数据',
        ];
    }

    /**
     * 表单数据
     * @return array
     */
    public function formData(): array
    {
        return [
            'name'          => $this->input('name'),
            'store_id'      => 1,
            'department_id' => $this->input('department_id'),
            'status'        => $this->input('status'),
            'remark'        => $this->input('remark')
        ];
    }
}
