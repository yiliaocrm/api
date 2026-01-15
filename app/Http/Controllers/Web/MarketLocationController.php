<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Store;
use Illuminate\Http\JsonResponse;

class MarketLocationController extends Controller
{
    public function index(): JsonResponse
    {
        $stores = Store::query()
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->select(['id', 'name', 'short_name', 'address', 'phone', 'latitude', 'longitude'])
            ->orderBy('name')
            ->get();

        return response_success($stores);
    }

    /**
     * 获取高德地图配置
     */
    public function amapConfig(): JsonResponse
    {
        return response_success([
            'key'    => admin_parameter('amap_key'),
            'secret' => admin_parameter('amap_secret')
        ]);
    }
}
