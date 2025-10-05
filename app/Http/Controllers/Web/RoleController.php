<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\RoleRequest;
use App\Models\Role;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Sentinel;

class RoleController extends Controller
{
    /**
     * 获取所有角色
     * @return JsonResponse
     */
    public function all(): JsonResponse
    {
        $roles = Role::query()->get();
        return response_success($roles);
    }

    public function manage(Request $request): JsonResponse
    {
        $rows  = $request->input('rows', 10);
        $sort  = $request->input('sort', 'id');
        $order = $request->input('order', 'desc');
        $query = Role::query()
            ->when($request->input('keyword'), fn(Builder $query, $keyword) => $query->where('keyword', 'like', "%{$keyword}%"))
            ->orderBy($sort, $order)
            ->paginate($rows);

        return response_success([
            'rows'  => $query->items(),
            'total' => $query->total(),
        ]);
    }

    /**
     * 创建角色
     * @param RoleRequest $request
     * @return JsonResponse
     */
    public function create(RoleRequest $request): JsonResponse
    {
        $role = Sentinel::getRoleRepository()->createModel()->create(
            $request->formData()
        );
        return response_success($role);
    }

    /**
     * 更新角色
     * @param RoleRequest $request
     * @return JsonResponse
     */
    public function update(RoleRequest $request): JsonResponse
    {
        $role = Role::query()->find(
            $request->input('id')
        );
        $role->update(
            $request->formData()
        );
        return response_success($role);
    }

    /**
     * 删除角色
     * @param RoleRequest $request
     * @return JsonResponse
     */
    public function remove(RoleRequest $request): JsonResponse
    {
        Sentinel::findRoleById($request->input('id'))->delete();
        return response_success();
    }

    /**
     * 保存权限
     * @param RoleRequest $request
     * @return JsonResponse
     */
    public function permission(RoleRequest $request): JsonResponse
    {
        $role = Sentinel::findRoleById(
            $request->input('id')
        );
        $role->update([
            'permissions' => $request->input('permission', []),
        ]);
        return response_success($role);
    }

    /**
     * 复制角色
     * @param RoleRequest $request
     * @return JsonResponse
     */
    public function copy(RoleRequest $request): JsonResponse
    {
        $role = Sentinel::findRoleById(
            $request->input('id')
        );

        $new       = $role->replicate();
        $new->slug = $role->slug . '-copy' . time();
        $new->name = $role->name . ' - 复制';
        $new->save();

        return response_success($role);
    }

    /**
     * 获取角色关联用户
     * @param RoleRequest $request
     * @return JsonResponse
     */
    public function users(RoleRequest $request): JsonResponse
    {
        $role  = Sentinel::findRoleById(
            $request->input('role_id')
        );
        $users = $role->users()->with(['department:id,name'])->paginate(
            $request->input('rows', 10)
        );
        return response_success([
            'rows'  => $users->items(),
            'total' => $users->total(),
        ]);
    }

    /**
     * 获取角色信息
     * @param RoleRequest $request
     * @return JsonResponse
     */
    public function info(RoleRequest $request): JsonResponse
    {
        $role = Sentinel::findRoleById(
            $request->input('id')
        );
        return response_success($role);
    }
}
