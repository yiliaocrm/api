<?php

namespace App\Http\Requests\Web;

use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Http\FormRequest;

class RoleRequest extends FormRequest
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
            default => [],
            'create' => [
                'name' => 'required|unique:roles',
                'slug' => 'required|unique:roles'
            ],
            'update' => [
                'name' => 'required|unique:roles,name,' . $this->input('id'),
                'slug' => 'required|unique:roles,slug,' . $this->input('id')
            ],
            'remove' => [
                'id' => [
                    'required',
                    'exists:roles',
                    function ($attribute, $value, $fail) {
                        if (DB::table('role_users')->where('role_id', $value)->exists()) {
                            $fail('该角色已被用户关联，无法删除！');
                        }
                    }
                ]
            ],
            'permission', 'copy', 'info' => [
                'id' => 'required|exists:roles'
            ],
            'users' => [
                'role_id' => 'required|exists:roles,id'
            ]
        };
    }

    public function messages(): array
    {
        return match (request()->route()->getActionMethod()) {
            default => [],
            'create' => [
                'name.required' => '[角色名称]不能为空！',
                'name.unique'   => '[角色名称]已存在！',
                'slug.required' => '[标签]不能为空！',
                'slug.unique'   => '[标签]重复！'
            ],
            'update' => [
                'name.required' => '[角色名称]不能为空！',
                'name.unique'   => '[角色名称]已存在！',
                'slug.required' => '[标签]不能为空！',
                'slug.unique'   => '[标签]已存在！'
            ],
            'remove', 'copy', 'permission', 'info' => [
                'id.required' => '[角色ID]不能为空！',
                'id.exists'   => '[角色ID]不存在！'
            ],
            'users' => [
                'role_id.required' => '[角色ID]不能为空！',
                'role_id.exists'   => '[角色ID]不存在！'
            ]
        };
    }

    public function formData(): array
    {
        return [
            'name'        => $this->input('name'),
            'slug'        => $this->input('slug'),
            'execution'   => $this->input('execution', false),
            'permissions' => $this->input('permissions', [])
        ];
    }
}
