<?php

namespace App\Http\Requests\Web;

use App\Models\Role;
use App\Models\User;
use App\Models\WebMenu;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PermissionQueryRequest extends FormRequest
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
     */
    public function rules(): array
    {
        return match (request()->route()->getActionMethod()) {
            'index' => $this->getIndexRules(),
            'user' => $this->getUserRules(),
            'role' => $this->getRoleRules(),
            'roleUser' => $this->getRoleUserRules(),
            'remove' => $this->getRemoveRules(),
            default => []
        };
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return match (request()->route()->getActionMethod()) {
            'index' => $this->getIndexMessages(),
            'user' => $this->getUserMessages(),
            'role' => $this->getRoleMessages(),
            'roleUser' => $this->getRoleUserMessages(),
            'remove' => $this->getRemoveMessages(),
            default => []
        };
    }

    /**
     * 获取index方法的验证规则
     */
    private function getIndexRules(): array
    {
        return [
            'keyword' => 'nullable|string|max:255',
            'type'    => [
                'required',
                'string',
                Rule::in(['web', 'app']),
            ],
        ];
    }

    /**
     * 获取index方法的错误消息
     */
    private function getIndexMessages(): array
    {
        return [
            'keyword.string' => '关键字必须是字符串。',
            'keyword.max'    => '关键字不能超过255个字符。',
            'type.required'  => '类型是必需的。',
            'type.string'    => '类型必须是字符串。',
            'type.in'        => '类型必须是web或app。',
        ];
    }

    private function getUserRules(): array
    {
        return [
            'menu_id' => [
                'required',
                'integer',
                function ($attribute, $value, $fail) {
                    $menu = WebMenu::query()->find($value);
                    if (!$menu || !$menu->permission) {
                        $fail('指定的菜单没有配置权限标识。');
                    }
                }
            ],
        ];
    }

    private function getUserMessages(): array
    {
        return [
            'menu_id.required' => '菜单ID是必需的。',
            'menu_id.integer'  => '菜单ID必须是整数。',
        ];
    }

    private function getRoleRules(): array
    {
        return [
            'menu_id' => [
                'required',
                'integer',
                function ($attribute, $value, $fail) {
                    $menu = WebMenu::query()->find($value);
                    if (!$menu || !$menu->permission) {
                        $fail('指定的菜单没有配置权限标识。');
                    }
                }
            ],
            'keyword' => 'nullable|string|max:255',
        ];
    }

    private function getRoleMessages(): array
    {
        return [
            'menu_id.required' => '菜单ID是必需的。',
            'menu_id.integer'  => '菜单ID必须是整数。',
            'keyword.string'   => '关键字必须是字符串。',
            'keyword.max'      => '关键字不能超过255个字符。',
        ];
    }

    private function getRoleUserRules(): array
    {
        return [
            'role_id' => 'required|integer|exists:roles,id',
        ];
    }

    private function getRoleUserMessages(): array
    {
        return [
            'role_id.required' => '角色ID是必需的。',
            'role_id.integer'  => '角色ID必须是整数。',
            'role_id.exists'   => '指定的角色ID不存在。',
        ];
    }

    private function getRemoveRules(): array
    {
        return [
            'menu_id' => [
                'required',
                'integer',
                function ($attribute, $value, $fail) {
                    $menu = WebMenu::query()->find($value);
                    if (!$menu || !$menu->permission) {
                        $fail('指定的菜单没有配置权限标识。');
                    }
                }
            ],
            'type'    => 'required|string|in:user,role',
            'type_id' => [
                'required',
                'integer',
                function ($attribute, $value, $fail) {
                    $type = request()->input('type');
                    if ($type === 'user') {
                        $exists = User::query()->where('id', $value)->exists();
                        if (!$exists) {
                            $fail('指定的用户ID不存在。');
                        }
                    }
                    if ($type === 'role') {
                        $exists = Role::query()->where('id', $value)->exists();
                        if (!$exists) {
                            $fail('指定的角色ID不存在。');
                        }
                    }
                }
            ],
        ];
    }

    private function getRemoveMessages(): array
    {
        return [
            'menu_id.required' => '菜单ID是必需的。',
            'menu_id.integer'  => '菜单ID必须是整数。',
            'type.required'    => '操作类型是必需的。',
            'type.string'      => '操作类型必须是字符串。',
            'type.in'          => '操作类型只能是user或role。',
            'type_id.required' => '对象ID是必需的。',
            'type_id.integer'  => '对象ID必须是整数。',
        ];
    }

    /**
     * 删除用户权限
     * @param string $permission
     * @param int $userId
     * @return void
     */
    public function removeUserPermissions(string $permission, int $userId): void
    {
        $user = User::query()->find($userId);
        if ($user) {
            $permissions              = $user->permissions ?? [];
            $permissions[$permission] = false;
            $user->update(['permissions' => $permissions]);
        }
    }

    /**
     * 删除角色权限
     * @param string $permission
     * @param int $roleId
     * @return void
     */
    public function removeRolePermissions(string $permission, int $roleId): void
    {
        $role = Role::query()->find($roleId);
        if ($role && $role->permissions) {
            $permissions = $role->permissions;
            unset($permissions[$permission]);
            $role->update(['permissions' => $permissions]);
        }
    }
}
