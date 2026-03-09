<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Horizon;

use App\Http\Controllers\Controller;
use App\Services\Admin\HorizonService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HorizonJobsController extends Controller
{
    public function __construct(
        protected HorizonService $horizon,
    ) {}

    public function pending(Request $request): JsonResponse
    {
        return response_success($this->horizon->pendingJobs(
            (int) $request->input('starting_at', -1)
        ));
    }

    public function completed(Request $request): JsonResponse
    {
        return response_success($this->horizon->completedJobs(
            (int) $request->input('starting_at', -1)
        ));
    }

    public function failed(Request $request): JsonResponse
    {
        return response_success($this->horizon->failedJobs(
            (int) $request->input('starting_at', -1),
            $request->input('tag')
        ));
    }

    public function failedDetail(Request $request): JsonResponse
    {
        return response_success($this->horizon->failedJobDetail($request->input('id', '')));
    }

    public function retry(Request $request): JsonResponse
    {
        return response_success($this->horizon->retryJob($request->input('id', '')));
    }

    public function silenced(Request $request): JsonResponse
    {
        return response_success($this->horizon->silencedJobs(
            (int) $request->input('starting_at', -1)
        ));
    }

    public function detail(Request $request): JsonResponse
    {
        return response_success($this->horizon->jobDetail($request->input('id', '')));
    }
}
