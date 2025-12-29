<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\AuthRequest;
use App\Events\Web\ScanQRCodeLoginEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;


class AuthController extends Controller
{
    /**
     * 返回系统版本号
     * @return JsonResponse
     */
    public function version(): JsonResponse
    {
        return response_success(
            admin_parameter('his_version')
        );
    }

    /**
     * 用户登陆
     * @param AuthRequest $request
     * @return JsonResponse
     */
    public function login(AuthRequest $request): JsonResponse
    {
        $user = User::query()->where('email', $request->input('email'))->first();
        if (!$user || !Hash::check($request->input('password'), $user->password)) {
            return response_error(msg: '账号或密码错误');
        }

        // 禁止登录
        if ($user->banned) {
            return response_error(msg: '账号已被禁用，请联系管理员解决！');
        }

        // 登录成功，写日志
        $user->loginLog()->create([
            'type'        => 2, // app
            'fingerprint' => $request->input("fingerprint")
        ]);

        $user->update([
            'last_login' => now()
        ]);

        return response_success([
            'access_token' => $user->createToken('app')->plainTextToken,
        ]);
    }

    /**
     * 返回登录用户信息
     * @return JsonResponse
     */
    public function profile(): JsonResponse
    {
        // 用户信息
        $user              = user();
        $user->permissions = $user->getMergedPermissions();

        // 系统配置
        $config = [
            'cywebos_hospital_name' => parameter('cywebos_hospital_name')
        ];

        $data = [
            'config'  => $config,
            'profile' => $user,
        ];

        return response_success($data);
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
     * 返回系统配置参数给前端
     * @return JsonResponse
     */
    public function config(): JsonResponse
    {
        $data = [
            'cywebos_hospital_name'                     => parameter('cywebos_hospital_name'),
            'consultant_allow_reception'                => parameter('consultant_allow_reception'),
            'cywebos_apps_autoload'                     => parameter('cywebos_apps_autoload'),
            'cashier_allow_modify'                      => parameter('cashier_allow_modify'),
            'cywebos_force_enable_google_authenticator' => parameter('cywebos_force_enable_google_authenticator'),
        ];
        return response_success($data);
    }

    /**
     * APP扫码登陆
     * @param AuthRequest $request
     * @return JsonResponse
     */
    public function qrcode(AuthRequest $request): JsonResponse
    {
        $uuid  = $request->input('uuid');
        $key   = "qrcode.login.{$uuid}";
        $token = auth('api')->user()->createToken('app')->plainTextToken;

        // 登录成功，写日志
        user()->loginLog()->create([
            'type'        => 3, // 扫码登陆
            'fingerprint' => $request->input("fingerprint")
        ]);

        // 用户登陆后,广播事件
        broadcast(new ScanQRCodeLoginEvent($uuid, $token));

        // 登陆成功,删除缓存
        Cache::forget($key);

        return response_success();
    }
}
