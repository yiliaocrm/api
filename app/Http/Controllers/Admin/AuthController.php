<?php

namespace App\Http\Controllers\Admin;

use App\Models\Admin\Admin;
use App\Models\Admin\AdminMenu;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Response;
use App\Http\Requests\Admin\AuthRequest;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class AuthController extends Controller
{
    /**
     * 登录页读取系统配置
     * @return JsonResponse
     */
    public function getConfig(): JsonResponse
    {
        return response_success([
            'his_version'       => admin_parameter('his_version'),
            'central_login_tfa' => admin_parameter('central_login_tfa'),
        ]);
    }

    /**
     * 登录页
     * @return SymfonyResponse
     */
    public function home(): SymfonyResponse
    {
        $file = public_path('dist/admin/index.html');
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
     * 管理员登录
     * @param AuthRequest $request
     * @return JsonResponse
     */
    public function login(AuthRequest $request): JsonResponse
    {
        $admin = Admin::query()->where('email', $request->input('username'))->first();
        if (!$admin || !Hash::check($request->input('password'), $admin->password)) {
            return response_error([], '账号或密码错误');
        }
        return response_success([
            'access_token' => $admin->createToken('admin')->plainTextToken,
        ]);
    }

    /**
     * 返回登录用户信息
     * @return JsonResponse
     */
    public function profile(): JsonResponse
    {
        $user = admin();
        $menu = AdminMenu::query()->orderBy('order')->orderBy('id')->get()->toArray();

        $user->makeHidden(['password']);

        return response_success([
            'user' => $user,
            'menu' => list_to_tree($menu)
        ]);
    }

    /**
     * 退出登录
     * @return JsonResponse
     */
    public function logout(): JsonResponse
    {
        if (admin()) {
            admin()->currentAccessToken()->delete();
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
        $user = admin();

        // 修改密码
        $user->update([
            'password' => bcrypt($request->input('password'))
        ]);

        // 删掉所有令牌
        $user->tokens()->delete();

        return response_success(msg: '密码修改成功!');
    }
}
