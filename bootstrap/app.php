<?php

use Illuminate\Support\Arr;
use App\Exceptions\HisException;
use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Application;
use App\Http\Middleware\TenantMiddleware;
use App\Http\Middleware\PermissionMiddleware;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Validation\ValidationException;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        using: function () {

            // 已经初始化系统 加载路由
            if (file_exists(storage_path('install.lock'))) {

                // 总后台路由
                $domains = config('tenancy.central_domains');
                foreach ($domains as $domain) {
                    Route::domain($domain)
                        ->prefix(config('tenancy.admin_path'))
                        ->middleware(['api', 'auth:sanctum'])
                        ->group(base_path('routes/admin.php'));
                }

                // 租户web路由
                Route::middleware([
                    InitializeTenancyByDomain::class,
                    PreventAccessFromCentralDomains::class,
                    'tenant',
                    'auth:sanctum',
                    'permission',
                ])
                    ->group(base_path('routes/web.php'));

                // 租户api路由
                Route::middleware([
                    InitializeTenancyByDomain::class,
                    PreventAccessFromCentralDomains::class,
                    'tenant',
                    'auth:sanctum',
                    'permission',
                ])
                    ->prefix('api')
                    ->group(base_path('routes/api.php'));

                // 租户微信路由
//                Route::middleware([
//                    InitializeTenancyByDomain::class,
//                    PreventAccessFromCentralDomains::class,
//                    'auth:sanctum',
//                ])
//                    ->prefix('wechat')
//                    ->group(base_path('routes/wechat.php'));

                // 租户h5路由
//                Route::middleware([
//                    'auth:sanctum',
//                    InitializeTenancyByDomain::class,
//                    PreventAccessFromCentralDomains::class,
//                ])
//                    ->prefix('h5')
//                    ->group(base_path('routes/h5.php'));

            } else {
                // 安装路由
                Route::middleware('web')->group(base_path('routes/install.php'));
            }

        },
        commands: __DIR__ . '/../routes/console.php'
    )
    ->withBroadcasting(
        __DIR__ . '/../routes/channels.php',
        ['middleware' => ['broadcast_tenancy_auth']]
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->validateCsrfTokens([
            '*'
        ]);
        $middleware->alias([
            'tenant'                 => TenantMiddleware::class,
            'permission'             => PermissionMiddleware::class,
            'broadcast_tenancy_auth' => \App\Http\Middleware\BroadcastTenancyAuth::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->renderable(function (AuthenticationException $e) {
            return response_error(msg: '认证失败', code: 401);
        });
        $exceptions->renderable(function (ValidationException $exception) {
            return response_error(msg: Arr::first($exception->errors())[0]);
        });
        $exceptions->renderable(function (UnauthorizedHttpException $exception) {
            return response_error(msg: 'token过期,请重新登录!', code: 401);
        });
        $exceptions->renderable(function (HisException $exception) {
            return response_error(msg: $exception->getMessage(), code: 500);
        });
    })->create();
