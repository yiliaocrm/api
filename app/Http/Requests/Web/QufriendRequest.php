<?php

namespace App\Http\Requests\Web;

use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Http\FormRequest;

class QufriendRequest extends FormRequest
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
            'remove' => $this->getRemoveRules(),
            'create' => $this->getCreateRules(),
            'update' => $this->getUpdateRules(),
            default => []
        };
    }

    public function messages(): array
    {
        return match (request()->route()->getActionMethod()) {
            'remove' => $this->getRemoveMessages(),
            'create' => $this->getCreateMessages(),
            'update' => $this->getUpdateMessages(),
            default => []
        };
    }

    private function getRemoveRules(): array
    {
        return [
            'id' => [
                'required',
                'integer',
                'exists:qufriends,id',
                function ($attribute, $value, $fail) {
                    if (DB::table('customer_qufriends')->where('qufriend_id', $value)->exists()) {
                        $fail('该亲友关系已被客户关联，无法删除');
                    }
                }
            ]
        ];
    }

    private function getRemoveMessages(): array
    {
        return [
            'id.required' => '亲友关系ID不能为空',
            'id.integer'  => '亲友关系ID必须为整数',
            'id.exists'   => '亲友关系ID不存在'
        ];
    }

    private function getCreateRules(): array
    {
        return [
            'name' => 'required|string|max:255|unique:qufriends,name'
        ];
    }

    private function getCreateMessages(): array
    {
        return [
            'name.required' => '亲友关系名称不能为空',
            'name.string'   => '亲友关系名称必须为字符串',
            'name.max'      => '亲友关系名称最大长度为255',
            'name.unique'   => '亲友关系名称已存在'
        ];
    }

    private function getUpdateRules(): array
    {
        return [
            'id'   => 'required|integer|exists:qufriends,id',
            'name' => 'required|string|max:255|unique:qufriends,name,' . $this->input('id')
        ];
    }

    private function getUpdateMessages(): array
    {
        return [
            'id.required'   => '亲友关系ID不能为空',
            'id.integer'    => '亲友关系ID必须为整数',
            'id.exists'     => '亲友关系ID不存在',
            'name.required' => '亲友关系名称不能为空',
            'name.string'   => '亲友关系名称必须为字符串',
            'name.max'      => '亲友关系名称最大长度为255',
            'name.unique'   => '亲友关系名称已存在'
        ];
    }

    public function formData(): array
    {
        return [
            'name' => $this->input('name'),
        ];
    }
}
