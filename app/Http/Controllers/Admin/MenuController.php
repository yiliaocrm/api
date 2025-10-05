<?php

namespace App\Http\Controllers\Admin;

use App\Models\Menu;
use App\Models\Tenant;
use App\Models\MenuPermissionScope;
use App\Jobs\SyncMenusToTenantJob;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MenuRequest;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Database\Eloquent\Builder;

class MenuController extends Controller
{
    /**
     * 获取所有菜单
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $keyword = $request->input('keyword');
        $menus   = Menu::query()
            ->where('type', $request->input('type', 'web'))
            ->when($keyword, function (Builder $query) use ($keyword) {

                // 执行包含关键字的原始查询
                $matching_tree_raw = Menu::query()
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
     * el-tree-select 树形结构
     * @param Request $request
     * @return JsonResponse
     */
    public function tree(Request $request)
    {
        $menus = Menu::query()
            ->where('type', $request->input('type', 'web'))
            ->orderBy('order', 'asc')
            ->get()
            ->toArray();
        return response_success(
            list_to_tree($menus)
        );
    }

    /**
     * 创建菜单
     * @param MenuRequest $request
     * @return JsonResponse
     */
    public function create(MenuRequest $request): JsonResponse
    {
        $menu = Menu::query()->create(
            $request->formData()
        );
        return response_success($menu);
    }

    /**
     * 更新菜单
     * @param MenuRequest $request
     * @return JsonResponse
     */
    public function update(MenuRequest $request): JsonResponse
    {
        $menu = Menu::query()->find(
            $request->input('id')
        );
        $menu->update(
            $request->formData()
        );
        return response_success($menu);
    }

    /**
     * 删除菜单
     * @param MenuRequest $request
     * @return JsonResponse
     */
    public function remove(MenuRequest $request): JsonResponse
    {
        Menu::query()->where('id', $request->input('id'))->delete();
        return response_success();
    }

    /**
     * 获取菜单权限范围数据
     * @return JsonResponse
     */
    public function scope(): JsonResponse
    {
        return response_success(
            MenuPermissionScope::query()->orderBy('order')->get()
        );
    }

    /**
     * 同步菜单到租户
     * @return JsonResponse
     */
    public function sync(): JsonResponse
    {
        $menus                  = DB::table('menus')->get()->map(fn($item) => (array)$item)->toArray();
        $tenants                = Tenant::query()->get();
        $menu_permission_scopes = DB::table('menu_permission_scopes')->get()->map(fn($item) => (array)$item)->toArray();

        // 给每一个租户派发更新菜单的任务
        foreach ($tenants as $tenant) {
            dispatch(new SyncMenusToTenantJob($tenant->id, $menus, $menu_permission_scopes));
        }

        return response_success();
    }

}
