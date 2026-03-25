<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\AuthRequest;
use App\Models\Admin\TenantLoginBanner;
use App\Models\Menu;
use App\Models\Store;
use App\Models\WebMenu;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Exceptions\IncompatibleWithGoogleAuthenticatorException;
use PragmaRX\Google2FA\Exceptions\InvalidCharactersException;
use PragmaRX\Google2FA\Exceptions\SecretKeyTooShortException;
use PragmaRX\Google2FA\Google2FA;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

/**
 * web端用户权限认证控制器
 */
class AuthController extends Controller
{
    /**
     * 首页
     */
    public function home(): SymfonyResponse
    {
        $file = public_path('/dist/his/index.html');
        if (file_exists($file)) {
            $content = file_get_contents($file);
            $content = str_replace('/dist/', admin_parameter('dist_path'), $content);

            return Response::make($content, 200, [
                'Content-Type' => 'text/html',
                'Cache-Control' => 'no-cache, no-store, must-revalidate',
                'Pragma' => 'no-cache',
                'Expires' => '0',
            ]);
        }
        abort(404);
    }

    /**
     * 新页面
     */
    public function new(): SymfonyResponse
    {
        $file = public_path('dist/web/index.html');
        if (file_exists($file)) {
            $content = file_get_contents($file);
            $content = str_replace('/dist/', admin_parameter('dist_path'), $content);

            return Response::make($content, 200, [
                'Content-Type' => 'text/html',
                'Cache-Control' => 'no-cache, no-store, must-revalidate',
                'Pragma' => 'no-cache',
                'Expires' => '0',
            ]);
        }
        abort(404);
    }

    /**
     * 返回系统配置参数给前端
     */
    public function getConfig(): JsonResponse
    {
        $oem = [
            'help_url' => admin_parameter('oem_help_url'),
            'app_qrcode' => admin_parameter('oem_app_qrcode'),
            'system_name' => admin_parameter('oem_system_name'),
            'system_logo' => admin_parameter('oem_system_logo'),
            'service_qrcode' => admin_parameter('oem_service_qrcode'),
            'service_phone' => admin_parameter('oem_service_phone'),
            'service_description' => admin_parameter('oem_service_description'),
        ];
        $stores = Store::query()->select(['id', 'name'])->get();
        $reverb = [
            'reverb_host' => admin_parameter('reverb_host'),
            'reverb_port' => admin_parameter('reverb_port'),
            'reverb_scheme' => admin_parameter('reverb_scheme'),
            'reverb_app_id' => admin_parameter('reverb_app_id'),
            'reverb_app_key' => admin_parameter('reverb_app_key'),
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
            'oem' => $oem,
            'config' => [
                'sql_group_tfa' => admin_parameter('sql_group_tfa'),
                'watermark_enable' => parameter('watermark_enable'),
                'customer_phone_click2show' => parameter('customer_phone_click2show'),
            ],
            'tenant' => [
                'id' => tenant()->id,
                'expire_date' => tenant()->expire_date,
            ],
            'reverb' => $reverb,
            'stores' => $stores,
            'banners' => $banners,
            'cywebos_hospital_name' => parameter('cywebos_hospital_name'),
            'consultant_allow_reception' => parameter('consultant_allow_reception'),
            'cashier_allow_modify' => parameter('cashier_allow_modify'),
            'cywebos_force_enable_google_authenticator' => parameter('cywebos_force_enable_google_authenticator'),
            'cywebos_enable_item_product_type_sync' => parameter('cywebos_enable_item_product_type_sync'),
            'reservation_allow_modify_medium' => parameter('reservation_allow_modify_medium'),
            'customer_allow_modify_medium' => parameter('customer_allow_modify_medium'),
        ];

        return response_success($config);
    }

    /**
     * 登录
     *
     * @throws IncompatibleWithGoogleAuthenticatorException
     * @throws InvalidCharactersException
     * @throws SecretKeyTooShortException
     */
    public function login(AuthRequest $request): JsonResponse
    {
        $user = $request->getLoginUser();

        // 用户不存在
        if (! $user) {
            return response_error(msg: '账号或密码错误');
        }

        // 传统账号密码登录需要验证密码
        if (! $request->input('code') && ! Hash::check($request->input('password'), $user->password)) {
            return response_error(msg: '账号或密码错误');
        }

        // 启用动态口令验证（仅适用于传统登录）
        if (! $request->input('code') && parameter('cywebos_force_enable_google_authenticator')) {
            if (! $user->secret) {
                return response_error(msg: '没有设置动态口令，无法登陆，请联系管理员解决！');
            }
            $google2fa = new Google2FA;
            if (! $google2fa->verifyKey($user->secret, $request->input('tfa'))) {
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
            'last_login' => now(),
        ]);

        return response_success([
            'access_token' => $user->createToken('web')->plainTextToken,
        ]);
    }

    /**
     * 返回登录用户信息
     */
    public function profile(AuthRequest $request): JsonResponse
    {
        $user = user();
        $user->permissions = $user->getMergedPermissions();

        // 管理员
        if ($user->isSuperUser()) {
            $menu = WebMenu::query()->where('display', 1)->where('type', 'web')->get()->toArray();
            $menu2 = Menu::query()->where('type', 'web')->orderBy('order')->orderBy('id')->get()->toArray();
        } else {
            $tree = WebMenu::query()->where('type', 'web')->whereIn('permission', array_keys(array_filter($user->permissions ?? [])))->get()->implode('tree', '-');
            $menu = WebMenu::query()->where('type', 'web')->whereIn('id', array_values(array_unique(explode('-', $tree))))->where('display', 1)->get()->toArray();
            $tree2 = Menu::query()->where('type', 'web')->whereIn('permission', array_keys(array_filter($user->permissions ?? [])))->orderBy('order')->orderBy('id')->get()->implode('tree', '-');
            $menu2 = Menu::query()->where('type', 'web')->whereIn('id', array_values(array_unique(explode('-', $tree2))))->orderBy('order')->orderBy('id')->get()->toArray();
        }

        return response_success([
            'user' => $user,
            'menu' => $menu,
            'menu2' => list_to_tree($menu2),
            'store' => store(),
        ]);
    }

    /**
     * 账号中心资料
     */
    public function userCenter(): JsonResponse
    {
        $user = user()->load([
            'department:id,name',
            'roles:id,slug,name',
        ]);

        return response_success([
            'user' => $this->userCenterPayload($user),
            'security' => [
                'force_totp' => filter_var(parameter('cywebos_force_enable_google_authenticator'), FILTER_VALIDATE_BOOLEAN),
            ],
        ]);
    }

    /**
     * 更新账号资料
     */
    public function updateProfile(AuthRequest $request): JsonResponse
    {
        $user = user();

        $user->forceFill([
            'avatar' => $request->input('avatar'),
            'name' => $request->input('name'),
            'extension' => $request->input('extension'),
            'remark' => $request->input('remark'),
        ])->save();

        return response_success(
            $this->userCenterPayload(
                $user->fresh()->load([
                    'department:id,name',
                    'roles:id,slug,name',
                ])
            )
        );
    }

    /**
     * 获取当前用户动态口令密钥
     *
     * @throws IncompatibleWithGoogleAuthenticatorException
     * @throws InvalidCharactersException
     * @throws SecretKeyTooShortException
     */
    public function getSecret(): JsonResponse
    {
        $google2fa = new Google2FA;
        $secretKey = $google2fa->generateSecretKey();
        $qrCodeUrl = $google2fa->getQRCodeUrl(
            admin_parameter('oem_system_name'),
            user()->email,
            $secretKey
        );

        return response_success([
            'secret' => $secretKey,
            'qrcode' => $qrCodeUrl,
        ]);
    }

    /**
     * 保存当前用户动态口令
     */
    public function postSecret(AuthRequest $request): JsonResponse
    {
        user()->update([
            'secret' => $request->input('secret'),
        ]);

        return response_success();
    }

    /**
     * 解绑当前用户动态口令
     */
    public function clearSecret(): JsonResponse
    {
        if (filter_var(parameter('cywebos_force_enable_google_authenticator'), FILTER_VALIDATE_BOOLEAN)) {
            return response_error(msg: '系统已开启动态口令强制验证，当前账号不允许解绑');
        }

        user()->update([
            'secret' => null,
        ]);

        return response_success();
    }

    /**
     * 获取当前用户登录日志
     */
    public function loginLogs(AuthRequest $request): JsonResponse
    {
        $rows = (int) $request->input('rows', 10);
        $page = (int) $request->input('page', 1);

        $query = user()->loginLog()
            ->latest('id')
            ->paginate($rows, ['*'], 'page', $page);

        $query->append(['type_text']);

        return response_success([
            'rows' => $query->items(),
            'total' => $query->total(),
        ]);
    }

    /**
     * 退出登录
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
     */
    public function resetPassword(AuthRequest $request): JsonResponse
    {
        $user = user();

        // 修改密码
        $user->update([
            'password' => bcrypt($request->input('password')),
        ]);

        // 删掉所有令牌
        $user->tokens()->delete();

        return response_success([], '密码修改成功!');
    }

    /**
     * 生成uuid
     */
    public function qrcode(AuthRequest $request): JsonResponse
    {
        // 没有登录,生成新的uuid
        if (! user()) {
            $ttl = 5 * 60;
            $uuid = (string) Str::uuid();
            Cache::put("qrcode.login.{$uuid}", $uuid, $ttl);

            return response_success([
                'ttl' => $ttl,
                'uuid' => $uuid,
            ]);
        }

        return response_error(msg: '已登录,无法生成uuid');
    }

    private function userCenterPayload($user): array
    {
        $formatDate = static fn (mixed $value): mixed => $value instanceof \DateTimeInterface
            ? $value->format('Y-m-d H:i:s')
            : $value;

        return [
            'id' => $user->id,
            'email' => $user->email,
            'name' => $user->name,
            'avatar' => $user->avatar,
            'remark' => $user->remark,
            'extension' => $user->extension,
            'last_login' => $formatDate($user->last_login),
            'created_at' => $formatDate($user->created_at),
            'banned' => (bool) $user->banned,
            'department' => $user->department ? [
                'id' => $user->department->id,
                'name' => $user->department->name,
            ] : null,
            'roles' => $user->roles
                ->map(fn ($role) => [
                    'id' => $role->id,
                    'slug' => $role->slug,
                    'name' => $role->name,
                ])
                ->values()
                ->toArray(),
            'has_secret' => filled($user->secret),
        ];
    }
}
