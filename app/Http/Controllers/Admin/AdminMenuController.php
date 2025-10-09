<?php

namespace App\Http\Controllers\Admin;

use App\Models\Admin\AdminMenu;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdminMenuRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Database\Eloquent\Builder;

class AdminMenuController extends Controller
{
    /**
     * 获取所有菜单
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $keyword = $request->input('keyword');
        $menus   = AdminMenu::query()
            ->when($keyword, function (Builder $query) use ($keyword) {
                // 执行包含关键字的原始查询
                $matching_tree_raw = AdminMenu::query()
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
     * @return JsonResponse
     */
    public function tree()
    {
        $menus = AdminMenu::query()
            ->orderBy('order')
            ->get()
            ->toArray();
        return response_success(
            list_to_tree($menus)
        );
    }

    /**
     * 创建菜单
     * @param AdminMenuRequest $request
     * @return JsonResponse
     */
    public function create(AdminMenuRequest $request)
    {
        $menu = AdminMenu::query()->create(
            $request->formData()
        );
        return response_success($menu);
    }

    /**
     * 更新菜单
     * @param AdminMenuRequest $request
     * @return JsonResponse
     */
    public function update(AdminMenuRequest $request): JsonResponse
    {
        $menu = AdminMenu::query()->find(
            $request->input('id')
        );
        $menu->update(
            $request->formData()
        );
        return response_success($menu);
    }

    /**
     * 删除菜单
     * @param AdminMenuRequest $request
     * @return JsonResponse
     */
    public function remove(AdminMenuRequest $request): JsonResponse
    {
        AdminMenu::query()->where('id', $request->input('id'))->delete();
        return response_success();
    }
}
