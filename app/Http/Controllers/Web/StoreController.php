<?php

namespace App\Http\Controllers\Web;

use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\StoreRequest;
use Illuminate\Database\Eloquent\Builder;

class StoreController extends Controller
{
    /**
     * 门店列表
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $rows  = $request->input('rows', 10);
        $sort  = $request->input('sort', 'id');
        $order = $request->input('order', 'desc');
        $name  = $request->input('name');
        $query = Store::query()
            ->when($name, fn(Builder $query) => $query->where('name', 'like', "%{$name}%"))
            ->orderBy($sort, $order)
            ->paginate($rows);

        return response_success([
            'rows'  => $query->items(),
            'total' => $query->total()
        ]);
    }

    /**
     * 创建门店
     * @param StoreRequest $request
     * @return JsonResponse
     */
    public function create(StoreRequest $request): JsonResponse
    {
        $store = Store::query()->create(
            $request->formData()
        );
        return response_success($store);
    }

    /**
     * 更新门店
     * @param StoreRequest $request
     * @return JsonResponse
     */
    public function update(StoreRequest $request): JsonResponse
    {
        $store = Store::query()->find(
            $request->input('id')
        );
        $store->update(
            $request->formData()
        );
        return response_success($store);
    }

    /**
     * 删除门店
     * @param StoreRequest $request
     * @return JsonResponse
     */
    public function remove(StoreRequest $request): JsonResponse
    {
        Store::query()->find($request->input('id'))->delete();
        return response_success();
    }

    /**
     * 获取高德地图配置
     * @return JsonResponse
     */
    public function amapConfig(): JsonResponse
    {
        return response_success([
            'key'    => admin_parameter('amap_key'),
            'secret' => admin_parameter('amap_secret')
        ]);
    }
}
