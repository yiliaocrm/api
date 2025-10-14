<?php

namespace App\Http\Requests\Admin;

use App\Models\Menu;
use Illuminate\Foundation\Http\FormRequest;

class MenuRequest extends FormRequest
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
            'remove', 'info' => $this->getRemoveRules(),
            default => []
        };
    }

    public function messages(): array
    {
        return match (request()->route()->getActionMethod()) {
            'create' => $this->getCreateMessages(),
            'update' => $this->getUpdateMessages(),
            'remove', 'info' => $this->getRemoveMessages(),
            default => []
        };
    }

    private function getCreateRules(): array
    {
        return [
            'parentid'   => 'nullable|integer|exists:menus,id',
            'name'       => 'nullable|string|max:255',
            'path'       => 'nullable|string|max:255',
            'component'  => 'nullable|string|max:255',
            'type'       => 'required|string|in:web,app',
            'permission' => 'nullable|string|max:255',
            'order'      => 'nullable|integer',
        ];
    }

    private function getCreateMessages(): array
    {
        return [
            'parentid.integer'  => '[上级菜单]必须是整数!',
            'parentid.exists'   => '[上级菜单]不存在!',
            'name.string'       => '[组件名称]必须是字符串!',
            'name.max'          => '[组件名称]不能超过255个字符!',
            'path.string'       => '[访问路径]必须是字符串!',
            'path.max'          => '[访问路径]不能超过255个字符!',
            'component.string'  => '[组件路径]必须是字符串!',
            'component.max'     => '[组件路径]不能超过255个字符!',
            'type.required'     => '[菜单类型]不能为空!',
            'type.string'       => '[菜单类型]必须是字符串!',
            'type.in'           => '[菜单类型]不合法!',
            'permission.string' => '[权限名称]必须是字符串!',
            'permission.max'    => '[权限名称]不能超过255个字符!',
            'order.integer'     => '[排序]必须是整数!',
            'order.max'         => '[排序]不能超过255个字符!',
        ];
    }

    private function getUpdateRules(): array
    {
        return [
            'id'         => 'required|exists:menus,id',
            'parentid'   => [
                'nullable',
                'integer',
                function ($attribute, $value, $fail) {
                    if ($value && !Menu::query()->find($value)) {
                        $fail('上级菜单不存在!');
                        return;
                    }
                    if ($value == $this->input('id')) {
                        $fail('上级菜单不能是自己!');
                        return;
                    }
                    $allChildren = Menu::query()->find($this->input('id'))->getAllChild();
                    if ($allChildren->contains($value)) {
                        $fail('上级菜单不能是自己的子菜单!');
                    }
                }
            ],
            'name'       => 'nullable|string|max:255',
            'path'       => 'nullable|string|max:255',
            'component'  => 'nullable|string|max:255',
            'type'       => 'required|string|in:web,app',
            'permission' => 'nullable|string|max:255',
            'order'      => 'nullable|integer',
        ];
    }

    private function getUpdateMessages(): array
    {
        return [
            'id.required'       => '[id参数]不能为空!',
            'id.exists'         => '[菜单]不存在!',
            'parentid.required' => '[上级菜单]不能为空!',
            'parentid.integer'  => '[上级菜单]必须是整数!',
            'parentid.exists'   => '[上级菜单]不存在!',
            'name.string'       => '[组件名称]必须是字符串!',
            'name.max'          => '[组件名称]不能超过255个字符!',
            'path.string'       => '[访问路径]必须是字符串!',
            'path.max'          => '[访问路径]不能超过255个字符!',
            'component.string'  => '[组件路径]必须是字符串!',
            'component.max'     => '[组件路径]不能超过255个字符!',
            'type.required'     => '[菜单类型]不能为空!',
            'type.string'       => '[菜单类型]必须是字符串!',
            'type.in'           => '[菜单类型]不合法!',
            'permission.string' => '[权限名称]必须是字符串!',
            'permission.max'    => '[权限名称]不能超过255个字符!',
            'order.integer'     => '[排序]必须是整数!',
            'order.max'         => '[排序]不能超过255个字符!',
        ];
    }

    private function getRemoveRules(): array
    {
        return [
            'id' => 'required|exists:menus,id',
        ];
    }

    private function getRemoveMessages(): array
    {
        return [
            'id.required' => 'id参数不能为空!',
            'id.exists'   => '菜单不存在!',
        ];
    }

    public function formData(): array
    {
        return [
            'parentid'         => (int)$this->input('parentid'),
            'name'             => $this->input('name'),
            'title'            => $this->input('meta.title'),
            'path'             => $this->input('path') ?? '',
            'meta'             => $this->input('meta', []),
            'order'            => $this->input('order') ?? 0,
            'remark'           => $this->input('remark'),
            'component'        => $this->input('component'),
            'permission'       => $this->input('permission') ?? '',
            'permission_scope' => $this->input('permission_scope', []),
            'type'             => $this->input('type', 'web'),
            'menu_type'        => $this->input('meta.type'),
        ];
    }
}
