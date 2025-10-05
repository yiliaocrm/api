<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\UnitRequest;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UnitController extends Controller
{
    public function manage(Request $request): JsonResponse
    {
        $rows    = $request->input('rows', 10);
        $sort    = $request->input('sort', 'id');
        $order   = $request->input('order', 'desc');
        $keyword = $request->input('keyword');
        $query   = Unit::query()
            ->when($keyword, fn(Builder $query) => $query->where('keyword', 'like', '%' . $keyword . '%'))
            ->orderBy($sort, $order)
            ->paginate($rows);

        return response_success([
            'rows'  => $query->items(),
            'total' => $query->total()
        ]);
    }

    /**
     * 新增计量单位
     * @param UnitRequest $request
     * @return JsonResponse
     */
    public function create(UnitRequest $request): JsonResponse
    {
        $unit = Unit::query()->create(
            $request->formData()
        );
        return response_success($unit);
    }

    /**
     * 更新数据
     * @param UnitRequest $request
     * @return JsonResponse
     */
    public function update(UnitRequest $request): JsonResponse
    {
        $unit = Unit::query()->find($request->input('id'));
        $unit->update(
            $request->formData()
        );
        return response_success($unit);
    }

    /**
     * 删除单位
     * @param UnitRequest $request
     * @return JsonResponse
     */
    public function remove(UnitRequest $request)
    {
        Unit::query()->find($request->input('id'))->delete();
        return response_success();
    }
}
