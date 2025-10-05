<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\PurchaseTypeRequest;
use Carbon\Carbon;
use App\Models\PurchaseType;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Database\Eloquent\Builder;

class PurchaseTypeController extends Controller
{
    /**
     * 类别列表
     * @param Request $request
     * @return JsonResponse
     */
    public function manage(Request $request): JsonResponse
    {
        $order = $request->input('id', 'desc');
        $sort  = $request->input('sort', 'id');
        $rows  = $request->input('rows', 10);
        $query = PurchaseType::query()
            ->when($request->input('created_at_start') && $request->input('created_at_end'), function ($query) use ($request) {
                $query->whereBetween('created_at', [
                    Carbon::parse($request->input('created_at_start')),
                    Carbon::parse($request->input('created_at_end'))->endOfDay()
                ]);
            })
            ->when($keyword = $request->input('keyword'), fn(Builder $query) => $query->whereLike('name', "%{$keyword}%"))
            ->orderBy($sort, $order)
            ->paginate($rows);;

        return response_success([
            'rows'  => $query->items(),
            'total' => $query->total(),
        ]);
    }

    /**
     * 创建类别
     * @param PurchaseTypeRequest $request
     * @return JsonResponse
     */
    public function create(PurchaseTypeRequest $request): JsonResponse
    {
        $type = PurchaseType::query()->create(
            $request->formData()
        );
        return response_success($type);
    }

    /**
     * 查看类别
     * @param PurchaseTypeRequest $request
     * @return JsonResponse
     */
    public function info(PurchaseTypeRequest $request): JsonResponse
    {
        $type = PurchaseType::query()->find(
            $request->input('id')
        );
        return response_success($type);
    }

    /**
     * 更新类别
     * @param PurchaseTypeRequest $request
     * @return JsonResponse
     */
    public function update(PurchaseTypeRequest $request): JsonResponse
    {
        $type = PurchaseType::query()->find(
            $request->input('id')
        );
        $type->update(
            $request->formData()
        );
        return response_success($type);
    }

    /**
     * 删除类别
     * @param PurchaseTypeRequest $request
     * @return JsonResponse
     */
    public function remove(PurchaseTypeRequest $request): JsonResponse
    {
        PurchaseType::query()->find($request->input('id'))->delete();
        return response_success();
    }
}
