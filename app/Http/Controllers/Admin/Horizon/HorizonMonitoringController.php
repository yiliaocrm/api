<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Horizon;

use App\Http\Controllers\Controller;
use App\Services\Admin\HorizonService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HorizonMonitoringController extends Controller
{
    public function __construct(
        protected HorizonService $horizon,
    ) {}

    public function index(): JsonResponse
    {
        return response_success($this->horizon->monitoring());
    }

    public function store(Request $request): JsonResponse
    {
        return response_success($this->horizon->storeMonitoring($request->input('tag', '')));
    }

    public function jobs(Request $request): JsonResponse
    {
        return response_success($this->horizon->monitoringJobs(
            $request->input('tag', ''),
            (int) $request->input('starting_at', 0),
            (int) $request->input('limit', 25)
        ));
    }

    public function destroy(Request $request): JsonResponse
    {
        return response_success($this->horizon->destroyMonitoring($request->input('tag', '')));
    }
}
