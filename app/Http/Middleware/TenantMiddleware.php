<?php

namespace App\Http\Middleware;

use Closure;
use Throwable;
use App\Models\OperationLog;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Http\Kernel;
use Symfony\Component\HttpFoundation\Response;

class TenantMiddleware
{
    /**
     * 租户中间件
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next): mixed
    {
        $tenant = tenant();

        if (!$tenant) {
            return $next($request);
        }

        if ($this->isTenantExpired($tenant)) {
            return $this->handleFailedCheck($request, '您的服务已到期，请联系管理员续费！');
        }

        if ($this->isTenantPaused($tenant)) {
            return $this->handleFailedCheck($request, '您的服务已暂停，请联系管理员！');
        }

        if (!$this->isIpAllowed($request)) {
            return $this->handleFailedCheck($request, '您的IP{' . $request->getClientIp() . '}不在白名单中！');
        }

        return $next($request);
    }

    /**
     * 在响应发送到浏览器后执行
     * @param Request $request
     * @param Response $response
     * @return void
     */
    public function terminate(Request $request, Response $response): void
    {
        // CLI 环境下不记录操作日志
        if (app()->runningInConsole()) {
            return;
        }

        // 只有在租户环境下才记录日志
        if (!tenant()) {
            return;
        }

        // 开启请求日志
        if (parameter('cywebos_enable_operation_log')) {
            return;
        }

        try {
            $this->logOperation($request, $response);
        } catch (Throwable $e) {
            // 记录日志失败不应该影响业务，静默处理
            logger()->error('操作日志记录失败', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * 检查租户是否过期
     * @param $tenant
     * @return bool
     */
    protected function isTenantExpired($tenant): bool
    {
        return $tenant->expire_date && Carbon::now()->gt($tenant->expire_date);
    }

    /**
     * 检查租户是否暂停
     * @param $tenant
     * @return bool
     */
    protected function isTenantPaused($tenant): bool
    {
        return $tenant->status === 'pause';
    }

    /**
     * 检查IP是否允许
     * @param Request $request
     * @return bool
     */
    protected function isIpAllowed(Request $request): bool
    {
        if (!parameter('cywebos_enable_whitelist')) {
            return true;
        }

        $count = DB::table('whitelists')
            ->whereRaw('INET_ATON(?) BETWEEN INET_ATON(start_ip) AND INET_ATON(end_ip)', [$request->getClientIp()])
            ->first();

        return (bool)$count;
    }

    /**
     * 处理检查失败
     * @param Request $request
     * @param string $message
     * @param int $code
     * @return mixed
     */
    protected function handleFailedCheck(Request $request, string $message, int $code = 403): mixed
    {
        if ($request->expectsJson()) {
            return response_error(msg: $message, code: $code);
        }
        return view('message', ['message' => $message]);
    }

    /**
     * 记录操作日志
     * @param Request $request
     * @param Response $response
     * @return void
     */
    protected function logOperation(Request $request, Response $response): void
    {
        $requestStartAt = app(Kernel::class)->requestStartedAt()->getPreciseTimestamp(3);

        // 计算执行时长（从 Laravel 开始处理请求到响应完成）
        $duration   = round(microtime(true) - ($requestStartAt / 1000), 2);
        $paramsJson = json_encode($request->all(), JSON_UNESCAPED_UNICODE);

        // 获取控制器和方法信息
        $route      = $request->route();
        $action     = $route ? $route->getActionMethod() : null;
        $controller = $route ? $route->getControllerClass() : null;

        // 记录日志
        OperationLog::query()->create([
            'user_id'     => user()?->id,
            'ip'          => $request->getClientIp(),
            'method'      => $request->method(),
            'controller'  => $controller,
            'action'      => $action,
            'url'         => $request->fullUrl(),
            'params'      => $paramsJson,
            'status_code' => $response->getStatusCode(),
            'duration'    => $duration,
            'user_agent'  => $request->userAgent(),
        ]);
    }
}
