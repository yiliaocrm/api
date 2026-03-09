<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Horizon;

use App\Http\Controllers\Controller;
use App\Services\Admin\HorizonService;
use Illuminate\Http\JsonResponse;

class HorizonDashboardController extends Controller
{
    public function __construct(
        protected HorizonService $horizon,
    ) {}

    public function stats(): JsonResponse
    {
        return response_success($this->horizon->stats());
    }

    public function workload(): JsonResponse
    {
        return response_success($this->horizon->workload());
    }

    public function masters(): JsonResponse
    {
        return response_success($this->horizon->masters());
    }
}
