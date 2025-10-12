<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\TenantLoginBannerRequest;
use App\Models\Admin\TenantLoginBanner;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;

class TenantLoginBannerController extends Controller
{
    /**
     * Banner 列表
     * @param TenantLoginBannerRequest $request
     * @return JsonResponse
     */
    public function index(TenantLoginBannerRequest $request): JsonResponse
    {
        $rows    = $request->input('rows', 10);
        $sort    = $request->input('sort', 'order');
        $order   = $request->input('order', 'asc');
        $keyword = $request->input('keyword');

        $query = TenantLoginBanner::query()
            ->when($keyword, fn(Builder $query) => $query->where('title', 'like', '%' . $keyword . '%'))
            ->orderBy($sort, $order)
            ->paginate($rows);

        return response_success([
            'rows'  => $query->items(),
            'total' => $query->total()
        ]);
    }

    /**
     * 创建 Banner
     * @param TenantLoginBannerRequest $request
     * @return JsonResponse
     */
    public function create(TenantLoginBannerRequest $request): JsonResponse
    {
        $banner = TenantLoginBanner::query()->create(
            $request->formData()
        );
        return response_success($banner);
    }

    /**
     * 更新 Banner
     * @param TenantLoginBannerRequest $request
     * @return JsonResponse
     */
    public function update(TenantLoginBannerRequest $request): JsonResponse
    {
        $banner = TenantLoginBanner::query()->find(
            $request->input('id')
        );
        $banner->update(
            $request->formData()
        );
        return response_success($banner);
    }

    /**
     * 删除 Banner
     * @param TenantLoginBannerRequest $request
     * @return JsonResponse
     */
    public function remove(TenantLoginBannerRequest $request): JsonResponse
    {
        TenantLoginBanner::query()->find($request->input('id'))->delete();
        return response_success();
    }

    /**
     * 查看 Banner 详情
     * @param TenantLoginBannerRequest $request
     * @return JsonResponse
     */
    public function info(TenantLoginBannerRequest $request): JsonResponse
    {
        $banner = TenantLoginBanner::query()->find(
            $request->input('id')
        );
        return response_success($banner);
    }

    /**
     * 切换 Banner 启用/禁用状态
     * @param TenantLoginBannerRequest $request
     * @return JsonResponse
     */
    public function toggle(TenantLoginBannerRequest $request): JsonResponse
    {
        $banner = TenantLoginBanner::query()->find($request->input('id'));
        $banner->update([
            'disabled' => !$banner->disabled
        ]);
        return response_success($banner);
    }
}
