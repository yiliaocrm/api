<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Horizon;

use App\Http\Controllers\Controller;
use App\Services\Admin\HorizonService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HorizonBatchesController extends Controller
{
    public function __construct(
        protected HorizonService $horizon,
    ) {}

    public function index(Request $request): JsonResponse
    {
        return response_success($this->horizon->batches(
            $request->input('query'),
            $request->input('before_id')
        ));
    }

    public function detail(Request $request): JsonResponse
    {
        return response_success($this->horizon->batchDetail($request->input('id', '')));
    }

    public function retry(Request $request): JsonResponse
    {
        return response_success($this->horizon->retryBatch($request->input('id', '')));
    }
}
