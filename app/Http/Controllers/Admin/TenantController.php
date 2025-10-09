<?php

namespace App\Http\Controllers\Admin;

use Exception;
use App\Models\Admin\Tenant;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\TenantRequest;
use Illuminate\Support\Str;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Builder;
use Stancl\Tenancy\Exceptions\TenantCouldNotBeIdentifiedById;

class TenantController extends Controller
{
    /**
     * 租户列表
     * @param TenantRequest $request
     * @return JsonResponse
     */
    public function index(TenantRequest $request): JsonResponse
    {
        $rows        = $request->input('rows', 10);
        $sort        = $request->input('sort', 'created_at');
        $order       = $request->input('order', 'desc');
        $keyword     = $request->input('keyword');
        $expire_date = $request->input('expire_date');
        $created_at  = $request->input('created_at');

        $query = Tenant::query()
            ->with([
                'domains:id,domain,tenant_id',
            ])
            ->when($keyword, function (Builder $query) use ($keyword) {
                $query->where(function (Builder $query) use ($keyword) {
                    $query->where('name', 'like', '%' . $keyword . '%')
                        ->orWhere('id', 'like', '%' . $keyword . '%')
                        ->orWhere('remark', 'like', '%' . $keyword . '%')
                        ->orWhereHas('domains', function (Builder $q) use ($keyword) {
                            $q->where('domain', 'like', '%' . $keyword . '%');
                        });
                });
            })
            ->when($created_at, fn(Builder $query) => $query->whereBetween('created_at', $created_at))
            ->when($expire_date, fn(Builder $query) => $query->whereBetween('expire_date', $expire_date))
            ->orderBy($sort, $order)
            ->paginate($rows);

        return response_success([
            'rows'  => $query->items(),
            'total' => $query->total()
        ]);
    }

    /**
     * 创建租户
     * @param TenantRequest $request
     * @return JsonResponse
     */
    public function create(TenantRequest $request): JsonResponse
    {
        // 创建租户
        $tenant = Tenant::query()->create(
            $request->createData()
        );
        // 绑定域名
        $tenant->domains()->createMany(
            $request->domainData()
        );
        // 同步菜单
        $request->syncMenus($tenant);
        return response_success($tenant);
    }

    /**
     * 更新租户
     * @param TenantRequest $request
     * @return JsonResponse
     */
    public function update(TenantRequest $request): JsonResponse
    {
        $tenant = Tenant::query()->find(
            $request->input('original_id')
        );

        // 更新租户信息
        $tenant->update(
            $request->updateData()
        );

        // 删除绑定域名
        $tenant->domains()->delete();

        // 重新绑定
        $tenant->domains()->createMany(
            $request->domainData()
        );
        return response_success($tenant);
    }

    /**
     * 暂停租户运行
     * @param TenantRequest $request
     * @return JsonResponse
     */
    public function pause(TenantRequest $request): JsonResponse
    {
        $tenant = Tenant::query()->find($request->input('id'));
        $tenant->update([
            'status' => 'pause'
        ]);
        return response_success($tenant);
    }

    /**
     * 恢复租户运行状态
     * @param TenantRequest $request
     * @return JsonResponse
     */
    public function run(TenantRequest $request): JsonResponse
    {
        $tenant = Tenant::query()->find(
            $request->input('id')
        );
        $tenant->update([
            'status' => 'run'
        ]);
        return response_success($tenant);
    }

    /**
     * 删除租户
     * @param TenantRequest $request
     * @return JsonResponse
     * @throws Exception
     */
    public function remove(TenantRequest $request): JsonResponse
    {
        Tenant::query()->find($request->input('id'))->delete();
        return response_success();
    }

    /**
     * 查看租户信息
     * @param TenantRequest $request
     * @return JsonResponse
     */
    public function info(TenantRequest $request): JsonResponse
    {
        $tenant = Tenant::query()->find(
            $request->input('id')
        );
        $tenant->load([
            'domains'
        ]);
        return response_success($tenant);
    }

    /**
     * 一键登录
     * @param TenantRequest $request
     * @return JsonResponse
     * @throws TenantCouldNotBeIdentifiedById
     */
    public function login(TenantRequest $request): JsonResponse
    {
        $code      = Str::uuid()->toString();
        $tenant    = Tenant::query()->find($request->input('id'));
        $expire_at = now()->addMinutes(5);

        tenancy()->initialize($tenant);

        // 机构端管理员(id=1)
        Cache::put('login_token_' . $code, 1, $expire_at);

        tenancy()->end();

        return response_success([
            'url'       => tenant_route($tenant->domains[0]->domain . '/#/login', 'login', ['code' => $code]),
            'expire_at' => $expire_at->toDateTimeString()
        ]);
    }
}
