<?php

namespace App\Http\Controllers\Admin;

use App\Models\Admin\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;

class DashboardController extends Controller
{
    /**
     * 仪表盘首页
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request)
    {
        $today      = Carbon::today();
        $monthEnd   = $today->copy()->endOfMonth();
        $monthStart = $today->copy()->startOfMonth();

        // 获取租户总数
        $total = Tenant::query()->count();

        // 获取本月新增数量
        $growth = Tenant::query()->whereBetween('created_at', [$monthStart, $monthEnd])->count();

        // 获取本月即将过期的数量（expire_date在本月内）
        $warning = Tenant::query()->whereBetween('expire_date', [$monthStart->toDateString(), $monthEnd->toDateString()])->count();

        // 获取已过期的数量
        $expired = Tenant::query()->where('expire_date', '<', $today->toDateString())->count();

        return response_success([
            'dashboard' => [
                'total'   => $total,
                'growth'  => $growth,
                'warning' => $warning,
                'expired' => $expired,
            ]
        ]);
    }
}
