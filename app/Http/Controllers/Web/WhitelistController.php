<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Whitelist;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WhitelistController extends Controller
{
    /**
     * 白名单状态
     * @return JsonResponse
     */
    public function status(): JsonResponse
    {
        return response_success([
            'status' => parameter('cywebos_enable_whitelist')
        ]);
    }

    /**
     * 启用白名单
     * @return JsonResponse
     */
    public function enable(): JsonResponse
    {
        setParameter('cywebos_enable_whitelist', 1);
        return response_success();
    }

    /**
     * 禁用白名单
     * @return JsonResponse
     */
    public function disable(): JsonResponse
    {
        setParameter('cywebos_enable_whitelist', 0);
        return response_success();
    }

    /**
     * 白名单列表
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $rows  = $request->input('rows', 10);
        $sort  = $request->input('sort', 'id');
        $order = $request->input('order', 'desc');
        $query = Whitelist::query()
            ->when($request->input('ip'), fn($query) => $query->whereRaw('INET_ATON(?) BETWEEN INET_ATON(start_ip) AND INET_ATON(end_ip)', [$request->input('ip')]))
            ->orderBy($sort, $order)
            ->paginate($rows);

        return response_success([
            'rows'  => $query->items(),
            'total' => $query->total()
        ]);
    }

    /**
     * 创建白名单
     * @param Request $request
     * @return JsonResponse
     */
    public function create(Request $request): JsonResponse
    {
        $request->validate(
            [
                'start_ip' => 'required|ip',
                'end_ip'   => 'required|ip'
            ],
            [
                'start_ip.ip' => '起始IP格式错误',
                'end_ip.ip'   => '结束IP格式错误'
            ]
        );
        $whitelist = Whitelist::query()->create([
            'start_ip'    => $request->input('start_ip'),
            'end_ip'      => $request->input('end_ip'),
            'description' => $request->input('description'),
        ]);
        return response_success($whitelist);
    }

    /**
     * 更新白名单
     * @param Request $request
     * @return JsonResponse
     */
    public function update(Request $request): JsonResponse
    {
        $request->validate(
            [
                'id'       => 'required|integer|exists:whitelists,id',
                'start_ip' => 'required|ip',
                'end_ip'   => 'required|ip'
            ],
            [
                'id.exists'   => '白名单不存在',
                'start_ip.ip' => '起始IP格式错误',
                'end_ip.ip'   => '结束IP格式错误'
            ]
        );
        $whitelist = Whitelist::query()->find(
            $request->input('id')
        );
        $whitelist->update(
            $request->only(['start_ip', 'end_ip', 'description'])
        );
        return response_success($whitelist);
    }

    /**
     * 删除白名单
     * @param Request $request
     * @return JsonResponse
     */
    public function remove(Request $request): JsonResponse
    {
        $request->validate(
            [
                'id' => 'required|integer|exists:whitelists,id'
            ],
            [
                'id.exists' => '白名单不存在'
            ]
        );
        Whitelist::query()->find(
            $request->input('id')
        )->delete();
        return response_success();
    }
}
