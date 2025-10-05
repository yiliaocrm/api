<?php

namespace App\Http\Controllers\Web;

use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;
use App\Http\Requests\Web\UserRequest;
use Illuminate\Database\Eloquent\Builder;
use Google\Authenticator\GoogleAuthenticator;
use Cartalyst\Sentinel\Laravel\Facades\Sentinel;

class UserController extends Controller
{
    /**
     * 用户列表
     * @param Request $request
     * @return JsonResponse
     */
    public function manage(Request $request): JsonResponse
    {
        $rows          = $request->input('rows', 10);
        $sort          = $request->input('sort', 'id');
        $order         = $request->input('order', 'desc');
        $roles         = $request->input('roles');
        $keyword       = $request->input('keyword');
        $department_id = $request->input('department_id');

        $query = User::query()
            ->with([
                'roles',
                'department'
            ])
            ->select(['users.*'])
            ->when($keyword,
                fn(Builder $query) => $query->whereAny(
                    [
                        'users.remark',
                        'users.keyword',
                        'users.extension',
                    ],
                    'like',
                    '%' . $keyword . '%'
                )
            )
            ->when($roles, fn(Builder $query) => $query->leftJoin('role_users', 'users.id', '=', 'role_users.user_id')->where('role_users.role_id', $roles))
            ->when($department_id, fn(Builder $query) => $query->where('users.department_id', $department_id))
            ->orderBy($sort, $order)
            ->paginate($rows);

        return response_success([
            'rows'  => $query->items(),
            'total' => $query->total()
        ]);
    }

    /**
     * 获取指定用户信息
     * @param UserRequest $request
     * @return JsonResponse
     */
    public function info(UserRequest $request): JsonResponse
    {
        $user = User::query()->find(
            $request->input('id')
        );
        $user->load('roles');
        return response_success($user);
    }

    /**
     * 创建用户
     * @param UserRequest $request
     * @return JsonResponse
     */
    public function create(UserRequest $request): JsonResponse
    {
        // 注册并激活用户
        $user = Sentinel::registerAndActivate(
            $request->formData()
        );

        // 设置角色
        $user->roles()->sync($request->input('roles'));

        return response_success($user);
    }

    /**
     * 更新用户信息
     * @param UserRequest $request
     * @return JsonResponse
     */
    public function edit(UserRequest $request): JsonResponse
    {
        $user = Sentinel::findById($request->input('id'));

        // 不能修改默认管理员权限组
        if ($user->id != 1) {
            $user->roles()->sync($request->input('roles'));
        }

        // 更新用户信息
        Sentinel::update($user, $request->formData());

        return response_success();
    }

    /**
     * 获取用户权限
     * @param UserRequest $request
     * @return JsonResponse
     */
    public function getPermission(UserRequest $request): JsonResponse
    {
        $data = User::query()->find($request->input('id'))->getMergedPermissions();
        return response_success($data);
    }

    /**
     * 设置私有权限
     * @param UserRequest $request
     * @return JsonResponse
     */
    public function postPermission(UserRequest $request): JsonResponse
    {
        $id         = $request->input('id');
        $permission = $request->input('permissions', []);
        $all        = Sentinel::findById($id)->getMergedPermissions();
        $deny       = array_diff_key($all, $permission);    // 拒绝权限

        if ($deny) {
            foreach ($deny as $key => $value) {
                $permission = array_merge($permission, [$key => false]);
            }
        }

        Sentinel::findById($id)->update([
            'permissions' => $permission
        ]);

        return response_success();
    }

    /**
     * 清空私有权限
     * @param UserRequest $request
     * @return JsonResponse
     */
    public function clearPermission(UserRequest $request): JsonResponse
    {
        Sentinel::findById($request->input('id'))->update([
            'permissions' => []
        ]);
        return response_success();
    }

    /**
     * 禁止登录
     * @param UserRequest $request
     * @return JsonResponse
     */
    public function ban(UserRequest $request): JsonResponse
    {
        User::find($request->input('id'))->ban();
        return response_success();
    }

    /**
     * 允许登录
     * @param UserRequest $request
     * @return JsonResponse
     */
    public function unban(UserRequest $request): JsonResponse
    {
        User::find($request->input('id'))->unban();
        return response_success();
    }

    /**
     * 生成密钥
     * @param Request $request
     * @return JsonResponse
     */
    public function getSecret(Request $request): JsonResponse
    {
        $g    = new GoogleAuthenticator();
        $data = [
            'secret' => $g->generateSecret()
        ];
        return response_success($data);
    }

    /**
     * 保存密钥
     * @param UserRequest $request
     * @return JsonResponse
     */
    public function postSecret(UserRequest $request): JsonResponse
    {
        User::query()->find($request->input('id'))->update([
            'secret' => $request->input('secret')
        ]);
        return response_success();
    }

    /**
     * 清空密钥
     * @param UserRequest $request
     * @return JsonResponse
     */
    public function clearSecret(UserRequest $request): JsonResponse
    {
        User::query()->find($request->input('id'))->update([
            'secret' => null
        ]);
        return response_success();
    }

    /**
     * 生成一键登录的code
     * @param UserRequest $request
     * @return JsonResponse
     */
    public function loginCode(UserRequest $request): JsonResponse
    {
        $code      = Str::uuid()->toString();
        $user_id   = $request->input('id');
        $expire_at = now()->addMinutes(5);

        // 写入缓存
        Cache::put('login_token_' . $code, $user_id, $expire_at);

        return response_success([
            'url'       => url('/#/login?code=' . $code),
            'expire_at' => $expire_at->toDateTimeString()
        ]);
    }
}
