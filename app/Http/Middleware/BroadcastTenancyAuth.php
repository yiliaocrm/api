<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Laravel\Sanctum\PersonalAccessToken;
use Stancl\Tenancy\Database\Models\Domain;

class BroadcastTenancyAuth
{
    public function handle(Request $request, Closure $next)
    {
        // 获取Bearer token
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        // 初始化tenancy
        try {
            $domain = $request->getHost();
            $tenant = Domain::where('domain', $domain)->first()?->tenant;

            if ($tenant) {
                tenancy()->initialize($tenant);
            }
        } catch (\Exception $e) {
            Log::error('Tenancy initialization failed', [
                'error'  => $e->getMessage(),
                'domain' => $request->getHost()
            ]);
        }

        // 验证Sanctum token
        try {
            $tokenModel = PersonalAccessToken::findToken($token);

            if ($tokenModel && $tokenModel->tokenable) {
                $user = $tokenModel->tokenable;

                // 手动设置认证用户 - 避免Auth::setUser的类型问题
                $request->setUserResolver(function () use ($user) {
                    return $user;
                });

                return $next($request);
            }
        } catch (\Exception $e) {
            Log::error('Token validation failed', [
                'error' => $e->getMessage()
            ]);
        }

        return response()->json(['message' => 'Unauthenticated'], 401);
    }
}
