<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

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
}
