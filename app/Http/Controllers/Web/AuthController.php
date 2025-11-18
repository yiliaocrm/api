<?php

namespace App\Http\Controllers\Web;

use App\Models\Menu;
use App\Models\Store;
use App\Models\WebMenu;
use App\Models\Admin\TenantLoginBanner;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\AuthRequest;
use Illuminate\Support\Str;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Response;
use PragmaRX\Google2FA\Google2FA;
use PragmaRX\Google2FA\Exceptions\InvalidCharactersException;
use PragmaRX\Google2FA\Exceptions\SecretKeyTooShortException;
use PragmaRX\Google2FA\Exceptions\IncompatibleWithGoogleAuthenticatorException;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

/**
 * web端用户权限认证控制器
 */
class AuthController extends Controller
{
    /**
     * 首页
     * @return SymfonyResponse
     */
    public function home(): SymfonyResponse
    {
        $file = public_path('/dist/his/index.html');
        if (file_exists($file)) {
            $content = file_get_contents($file);
            $content = str_replace('/dist/', admin_parameter('dist_path'), $content);
            return Response::make($content, 200, [
                'Content-Type'  => 'text/html',
                'Cache-Control' => 'no-cache, no-store, must-revalidate',
                'Pragma'        => 'no-cache',
                'Expires'       => '0'
            ]);
        }
        abort(404);
    }

    /**
     * 新页面
     * @return SymfonyResponse
     */
    public function new(): SymfonyResponse
    {
        $file = public_path('dist/web/index.html');
        if (file_exists($file)) {
            $content = file_get_contents($file);
            $content = str_replace('/dist/', admin_parameter('dist_path'), $content);
            return Response::make($content, 200, [
                'Content-Type'  => 'text/html',
                'Cache-Control' => 'no-cache, no-store, must-revalidate',
                'Pragma'        => 'no-cache',
                'Expires'       => '0'
            ]);
        }
        abort(404);
    }

    /**
     * 返回系统配置参数给前端
     * @return JsonResponse
     */
    public function getConfig(): JsonResponse
    {
        $oem    = [
            'help_url'            => admin_parameter('oem_help_url'),
            'app_qrcode'          => admin_parameter('oem_app_qrcode'),
            'system_name'         => admin_parameter('oem_system_name'),
            'system_logo'         => admin_parameter('oem_system_logo'),
            'service_qrcode'      => admin_parameter('oem_service_qrcode'),
            'service_phone'       => admin_parameter('oem_service_phone'),
            'service_description' => admin_parameter('oem_service_description'),
        ];
        $stores = Store::query()->select(['id', 'name'])->get();
        $reverb = [
            'reverb_host'       => admin_parameter('reverb_host'),
            'reverb_port'       => admin_parameter('reverb_port'),
            'reverb_scheme'     => admin_parameter('reverb_scheme'),
            'reverb_app_id'     => admin_parameter('reverb_app_id'),
            'reverb_app_key'    => admin_parameter('reverb_app_key'),
            'reverb_app_secret' => admin_parameter('reverb_app_secret'),
        ];

        // 从中央数据库读取未禁用的登录轮播图
        $banners = tenancy()->central(function () {
            return TenantLoginBanner::query()
                ->where('disabled', false)
                ->orderBy('order')
                ->get();
        });

        $config = [
            'oem'                                       => $oem,
            'config'                                    => [
                'sql_group_tfa'             => admin_parameter('sql_group_tfa'),
                'watermark_enable'          => parameter('watermark_enable'),
                'customer_phone_click2show' => parameter('customer_phone_click2show'),
            ],
            'tenant'                                    => [
                'id'          => tenant()->id,
                'expire_date' => tenant()->expire_date
            ],
            'reverb'                                    => $reverb,
            'stores'                                    => $stores,
            'banners'                                   => $banners,
            'cywebos_hospital_name'                     => parameter('cywebos_hospital_name'),
            'consultant_allow_reception'                => parameter('consultant_allow_reception'),
            'cashier_allow_modify'                      => parameter('cashier_allow_modify'),
            'cywebos_force_enable_google_authenticator' => parameter('cywebos_force_enable_google_authenticator'),
            'cywebos_enable_item_product_type_sync'     => parameter('cywebos_enable_item_product_type_sync'),
            'reservation_allow_modify_medium'           => parameter('reservation_allow_modify_medium'),
            'customer_allow_modify_medium'              => parameter('customer_allow_modify_medium'),
        ];
        return response_success($config);
    }

    /**
     * 登录
     * @param AuthRequest $request
     * @return JsonResponse
     * @throws IncompatibleWithGoogleAuthenticatorException
     * @throws InvalidCharactersException
     * @throws SecretKeyTooShortException
     */
    public function login(AuthRequest $request): JsonResponse
    {
        $user = $request->getLoginUser();

        // 用户不存在
        if (!$user) {
            return response_error(msg: '账号或密码错误');
        }

        // 传统账号密码登录需要验证密码
        if (!$request->input('code') && !Hash::check($request->input('password'), $user->password)) {
            return response_error(msg: '账号或密码错误');
        }

        // 启用动态口令验证（仅适用于传统登录）
        if (!$request->input('code') && parameter('cywebos_force_enable_google_authenticator')) {
            if (!$user->secret) {
                return response_error(msg: '没有设置动态口令，无法登陆，请联系管理员解决！');
            }
            $google2fa = new Google2FA();
            if (!$google2fa->verifyKey($user->secret, $request->input('tfa'))) {
                return response_error(msg: '动态口令验证失败!');
            }
        }

        // 禁止登录
        if ($user->banned) {
            return response_error(msg: '账号已被禁用，请联系管理员解决！');
        }

        // 登录成功，写日志
        $user->loginLog()->create(
            $request->getLoginLogData()
        );

        $user->update([
            'last_login' => now()
        ]);

        return response_success([
            'access_token' => $user->createToken('web')->plainTextToken,
        ]);
    }

    /**
     * 返回登录用户信息
     * @param AuthRequest $request
     * @return JsonResponse
     */
    public function profile(AuthRequest $request): JsonResponse
    {
        $user              = user();
        $user->permissions = $user->getMergedPermissions();

        // 管理员
        if ($user->isSuperUser()) {
            $menu  = WebMenu::query()->where('display', 1)->where('type', 'web')->get()->toArray();
            $menu2 = Menu::query()->where('type', 'web')->orderBy('order')->orderBy('id')->get()->toArray();
        } else {
            $tree  = WebMenu::query()->where('type', 'web')->whereIn('permission', array_keys(array_filter($user->permissions ?? [])))->get()->implode('tree', '-');
            $menu  = WebMenu::query()->where('type', 'web')->whereIn('id', array_values(array_unique(explode('-', $tree))))->where('display', 1)->get()->toArray();
            $tree2 = Menu::query()->where('type', 'web')->whereIn('permission', array_keys(array_filter($user->permissions ?? [])))->orderBy('order')->orderBy('id')->get()->implode('tree', '-');
            $menu2 = Menu::query()->where('type', 'web')->whereIn('id', array_values(array_unique(explode('-', $tree2))))->orderBy('order')->orderBy('id')->get()->toArray();
        }

        return response_success([
            'user'  => $user,
            'menu'  => $menu,
            'menu2' => list_to_tree($menu2),
            'store' => store()
        ]);
    }

    /**
     * 退出登录
     * @return JsonResponse
     */
    public function logout(): JsonResponse
    {
        if (user()) {
            user()->currentAccessToken()->delete();
        }
        return response_success();
    }

    /**
     * 修改密码
     * @param AuthRequest $request
     * @return JsonResponse
     */
    public function resetPassword(AuthRequest $request): JsonResponse
    {
        $user = user();

        // 修改密码
        $user->update([
            'password' => bcrypt($request->input('password'))
        ]);

        // 删掉所有令牌
        $user->tokens()->delete();

        return response_success([], '密码修改成功!');
    }

    /**
     * 生成uuid
     * @param AuthRequest $request
     * @return JsonResponse
     */
    public function qrcode(AuthRequest $request): JsonResponse
    {
        // 没有登录,生成新的uuid
        if (!user()) {
            $ttl  = 5 * 60;
            $uuid = (string)Str::uuid();
            Cache::put("qrcode.login.{$uuid}", $uuid, $ttl);
            return response_success([
                'ttl'  => $ttl,
                'uuid' => $uuid,
            ]);
        }
        return response_error(msg: '已登录,无法生成uuid');
    }
}
