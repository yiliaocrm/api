<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\PersonalAccessToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TokenController extends Controller
{
    /**
     * 令牌列表
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $rows  = $request->input('rows', 10);
        $sort  = $request->input('sort', 'id');
        $order = $request->input('order', 'desc');
        $query = PersonalAccessToken::query()
            ->with(['tokenable'])
            ->when($request->input('tokenable_type'), fn($query) => $query->where('tokenable_type', $request->input('tokenable_type')))
            ->when($request->input('name'), fn($query) => $query->where('name', $request->input('name')))
            ->orderBy($sort, $order)
            ->paginate($rows);

        return response_success([
            'rows'  => $query->items(),
            'total' => $query->total(),
        ]);
    }

    /**
     * 删除令牌
     * @param Request $request
     * @return JsonResponse
     */
    public function remove(Request $request): JsonResponse
    {
        $request->validate([
            'id' => 'required|integer|exists:personal_access_tokens',
        ]);
        PersonalAccessToken::query()
            ->where('id', $request->input('id'))
            ->delete();
        return response_success();
    }
}
