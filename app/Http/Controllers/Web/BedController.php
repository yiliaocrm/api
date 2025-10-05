<?php

namespace App\Http\Controllers\Web;

use App\Models\Bed;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\BedRequest;
use Illuminate\Database\Eloquent\Builder;

class BedController extends Controller
{
    /**
     * 床位管理
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $rows  = $request->input('rows', 10);
        $sort  = $request->input('sort', 'id');
        $order = $request->input('order', 'desc');
        $query = Bed::query()
            ->with([
                'store:id,name'
            ])
            ->when($name = $request->input('name'), fn(Builder $query) => $query->where('name', 'like', "%{$name}%"))
            ->orderBy($sort, $order)
            ->paginate($rows);

        return response_success([
            'rows'  => $query->items(),
            'total' => $query->total(),
        ]);
    }

    /**
     * 创建床位
     * @param BedRequest $request
     * @return JsonResponse
     */
    public function create(BedRequest $request): JsonResponse
    {
        $bed = Bed::query()->create(
            $request->formData()
        );
        return response_success($bed);
    }

    /**
     * 更新床位图
     * @param BedRequest $request
     * @return JsonResponse
     */
    public function update(BedRequest $request): JsonResponse
    {
        $bed = Bed::query()->find(
            $request->input('id')
        );
        $bed->update(
            $request->formData()
        );
        return response_success($bed);
    }

    /**
     * 删除床位图
     * @param BedRequest $request
     * @return JsonResponse
     */
    public function remove(BedRequest $request): JsonResponse
    {
        Bed::query()->find($request->input('id'))->delete();
        return response_success();
    }
}
