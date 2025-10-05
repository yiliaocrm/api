<?php

namespace App\Http\Controllers\Web;

use App\Models\Role;
use App\Models\User;
use App\Models\WebMenu;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Builder;
use App\Http\Requests\Web\PermissionQueryRequest;

class PermissionQueryController extends Controller
{
    /**
     * 菜单列表
     * @param PermissionQueryRequest $request
     * @return JsonResponse
     */
    public function index(PermissionQueryRequest $request): JsonResponse
    {
        $keyword = $request->input('keyword');
        $menus   = WebMenu::query()
            ->where('type', $request->input('type', 'web'))
            ->when($keyword, function (Builder $query) use ($keyword) {

                // 执行包含关键字的原始查询
                $matching_tree_raw = WebMenu::query()
                    ->selectRaw("REPLACE(tree, '-', ',') AS tree")
                    ->where('keyword', 'LIKE', '%' . $keyword . '%')
                    ->get();

                // 从上述查询结果中提取 ID
                $ids = [];
                foreach ($matching_tree_raw as $item) {
                    $ids = array_merge($ids, explode(',', $item->tree));
                }

                // 将提取的 ID 添加到主查询的 whereIn 子句中
                return $query->whereIn('id', array_unique($ids));
            })
            ->orderBy('order')
            ->orderBy('id')
            ->get();
        return response_success($menus);
    }

    /**
     * 私有权限
     * @param PermissionQueryRequest $request
     * @return JsonResponse
     */
    public function user(PermissionQueryRequest $request): JsonResponse
    {
        $menu    = WebMenu::query()->find($request->input('menu_id'));
        $rows    = $request->input('rows', 10);
        $sort    = $request->input('sort', 'id');
        $order   = $request->input('order', 'desc');
        $keyword = $request->input('keyword');

        $query = User::query()
            ->with([
                'roles:id,name',
                'department:id,name',
            ])
            ->select(['id', 'name', 'department_id', 'last_login', 'created_at'])
            ->when($keyword, fn(Builder $query) => $query->whereLike('keyword', "%{$keyword}%"))
            ->whereNotNull('permissions')
            ->where('permissions', '!=', '')
            ->where('permissions', '!=', '{}')
            ->whereJsonContains('permissions', [$menu->permission => true])
            ->orderBy($sort, $order)
            ->paginate($rows);

        return response_success([
            'rows'  => $query->items(),
            'total' => $query->total()
        ]);
    }

    /**
     * 角色权限
     * @param PermissionQueryRequest $request
     * @return JsonResponse
     */
    public function role(PermissionQueryRequest $request): JsonResponse
    {
        $menu    = WebMenu::query()->find($request->input('menu_id'));
        $rows    = $request->input('rows', 10);
        $sort    = $request->input('sort', 'id');
        $order   = $request->input('order', 'desc');
        $keyword = $request->input('keyword');

        $query = Role::query()
            ->addSelect([
                'users_count' => function ($query) {
                    $query->selectRaw('count(*)')
                        ->from('role_users')
                        ->whereColumn('role_users.role_id', 'roles.id');
                }
            ])
            ->when($keyword, fn(Builder $query) => $query->where('keyword', 'like', "%{$keyword}%"))
            ->whereNotNull('permissions')
            ->where('permissions', '!=', '')
            ->where('permissions', '!=', '{}')
            ->where('permissions', '!=', '[]')
            ->whereJsonContains('permissions', [$menu->permission => true])
            ->orderBy($sort, $order)
            ->paginate($rows);

        return response_success([
            'rows'  => $query->items(),
            'total' => $query->total()
        ]);
    }

    /**
     * 角色用户列表
     * @param PermissionQueryRequest $request
     * @return JsonResponse
     */
    public function roleUser(PermissionQueryRequest $request): JsonResponse
    {
        $rows   = $request->input('rows', 10);
        $sort   = $request->input('sort', 'id');
        $order  = $request->input('order', 'desc');
        $roleId = $request->input('role_id');

        $query = User::query()
            ->with([
                'roles:id,name',
                'department:id,name',
            ])
            ->select(['id', 'name', 'department_id', 'created_at', 'last_login'])
            ->whereHas('roles', function (Builder $query) use ($roleId) {
                $query->where('role_id', $roleId);
            })
            ->orderBy($sort, $order)
            ->paginate($rows);

        return response_success([
            'rows'  => $query->items(),
            'total' => $query->total()
        ]);
    }

    /**
     * 删除权限
     * @param PermissionQueryRequest $request
     * @return JsonResponse
     */
    public function remove(PermissionQueryRequest $request): JsonResponse
    {
        $menu   = WebMenu::query()->find($request->input('menu_id'));
        $type   = $request->input('type');
        $typeId = $request->input('type_id');

        if ($type === 'user') {
            $request->removeUserPermissions($menu->permission, $typeId);
        } else {
            $request->removeRolePermissions($menu->permission, $typeId);
        }

        return response_success();
    }
}
