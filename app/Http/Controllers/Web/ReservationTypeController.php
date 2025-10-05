<?php

namespace App\Http\Controllers\Web;

use App\Models\ReservationType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\ReservationTypeRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Database\Eloquent\Builder;

class ReservationTypeController extends Controller
{
    public function manage(Request $request): JsonResponse
    {
        $rows  = $request->input('rows', 10);
        $sort  = $request->input('sort', 'id');
        $order = $request->input('order', 'desc');
        $query = ReservationType::query()
            ->when($name = $request->input('name'), fn(Builder $query) => $query->whereLike('name', "%{$name}%"))
            ->orderBy($sort, $order)
            ->paginate($rows);

        return response_success([
            'rows'  => $query->items(),
            'total' => $query->total(),
        ]);
    }

    /**
     * 创建
     * @param ReservationTypeRequest $request
     * @return JsonResponse
     */
    public function create(ReservationTypeRequest $request): JsonResponse
    {
        $data = ReservationType::query()->create(
            $request->formData()
        );
        return response_success($data);
    }

    /**
     * 更新
     * @param ReservationTypeRequest $request
     * @return JsonResponse
     */
    public function update(ReservationTypeRequest $request): JsonResponse
    {
        $data = ReservationType::query()->find(
            $request->input('id')
        );
        $data->update(
            $request->formData()
        );
        return response_success($data);
    }

    /**
     * 删除
     * @param ReservationTypeRequest $request
     * @return JsonResponse
     */
    public function remove(ReservationTypeRequest $request): JsonResponse
    {
        ReservationType::query()->find($request->input('id'))->delete();
        return response_success();
    }
}
