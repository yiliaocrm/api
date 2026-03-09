<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Horizon;

use App\Http\Controllers\Controller;
use App\Services\Admin\HorizonService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HorizonMetricsController extends Controller
{
    public function __construct(
        protected HorizonService $horizon,
    ) {}

    public function jobMetrics(): JsonResponse
    {
        return response_success($this->horizon->jobMetrics());
    }

    public function jobMetricDetail(Request $request): JsonResponse
    {
        return response_success($this->horizon->jobMetricDetail($request->input('id', '')));
    }

    public function queueMetrics(): JsonResponse
    {
        return response_success($this->horizon->queueMetrics());
    }

    public function queueMetricDetail(Request $request): JsonResponse
    {
        return response_success($this->horizon->queueMetricDetail($request->input('id', '')));
    }
}
