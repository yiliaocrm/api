<?php

namespace App\Http\Requests\Web;

use Illuminate\Foundation\Http\FormRequest;

class UserGroupRequest extends FormRequest
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
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string|max:255',
            'ids'         => 'required|array',
            'ids.*'       => 'required|integer|exists:users,id'
        ];
    }

    private function getCreateMessages(): array
    {
        return [
            'name.required'      => '[工作组名称]不能为空',
            'name.string'        => '[工作组名称]必须为字符串',
            'name.max'           => '[工作组名称]最大长度为255',
            'description.string' => '[工作组描述]必须为字符串',
            'description.max'    => '[工作组描述]最大长度为255',
            'ids.required'       => '[成员]不能为空',
            'ids.array'          => '[成员]必须为数组',
            'ids.*.required'     => '[成员]不能为空',
            'ids.*.integer'      => '[成员]必须为整数',
            'ids.*.exists'       => '[成员]不存在'
        ];
    }

    private function getUpdateRules(): array
    {
        return [
            'id'          => 'required|exists:user_groups,id',
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string|max:255',
            'ids'         => 'required|array',
            'ids.*'       => 'required|exists:users,id'
        ];
    }

    private function getUpdateMessages(): array
    {
        return [
            'id.required'        => '缺少id参数',
            'id.exists'          => '没有找到数据',
            'name.required'      => '[工作组名称]不能为空',
            'name.string'        => '[工作组名称]必须为字符串',
            'name.max'           => '[工作组名称]最大长度为255',
            'description.string' => '[工作组描述]必须为字符串',
            'description.max'    => '[工作组描述]最大长度为255',
            'ids.required'       => '[成员]不能为空',
            'ids.array'          => '[成员]必须为数组',
            'ids.*.required'     => '[成员]不能为空',
            'ids.*.exists'       => '[成员]不存在'
        ];
    }

    private function getRemoveRules(): array
    {
        return [
            'id' => 'required|exists:user_groups,id'
        ];
    }

    private function getRemoveMessages(): array
    {
        return [
            'id.required' => '缺少id参数',
            'id.exists'   => '没有找到数据'
        ];
    }

    /**
     * 表单数据
     * @return array
     */
    public function formData(): array
    {
        return [
            'name'           => $this->input('name'),
            'description'    => $this->input('description'),
            'store_id'       => 1,
            'create_user_id' => user()->id
        ];
    }
}
