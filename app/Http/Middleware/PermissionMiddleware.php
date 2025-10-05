<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Contracts\Container\BindingResolutionException;

class PermissionMiddleware
{
    /**
     * 权限中间件
     * @param Request $request
     * @param Closure $next
     * @return Response
     * @throws BindingResolutionException
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 超级用户直接放行
        if (!user() || user()->isSuperUser()) {
            return $next($request);
        }

        // 获取当前请求的控制器和方法
        $action     = $request->route()->getActionMethod();
        $controller = get_class($request->route()->getController());

        // 查询控制器的所有权限
        $permissionActions = DB::table('permission_actions')->where('controller', $controller)->get();

        // 控制器没有定义权限，直接放行
        if ($permissionActions->isEmpty()) {
            return $next($request);
        }

        // 获取当前用户的所有权限
        $userPermissions = array_keys(array_filter(user()->getMergedPermissions()));

        // 查找适用于当前操作的权限
        $requiredPermissions = [];
        foreach ($permissionActions as $permissionAction) {
            $actions = !empty($permissionAction->action) ? explode(',', $permissionAction->action) : [];
            $excepts = !empty($permissionAction->except) ? explode(',', $permissionAction->except) : [];

            // 检查是否在排除列表
            if (in_array($action, $excepts)) {
                continue;
            }

            // 检查是否匹配
            // 1. action 是 '*' 且当前方法不在 except 列表
            // 2. 当前方法在 action 列表
            if (($permissionAction->action === '*' && !in_array($action, $excepts)) || in_array($action, $actions)) {
                $requiredPermissions[] = $permissionAction->permission;
            }
        }

        // 如果没有权限规则适用于此操作，则放行
        if (empty($requiredPermissions)) {
            return $next($request);
        }

        // 检查用户是否拥有所需权限之一
        if (count(array_intersect($userPermissions, $requiredPermissions)) > 0) {
            return $next($request);
        }

        // 没有找到匹配的权限，返回403
        return response_success(msg: '暂无权限', code: 403);
    }
}
