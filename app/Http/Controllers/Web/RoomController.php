<?php

namespace App\Http\Controllers\Web;

use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\RoomRequest;
use Illuminate\Database\Eloquent\Builder;

class RoomController extends Controller
{
    /**
     * 治疗室管理
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $rows  = $request->input('rows', 10);
        $sort  = $request->input('sort', 'id');
        $order = $request->input('order', 'desc');
        $name  = $request->input('name');
        $query = Room::query()
            ->with([
                'store:id,name',
                'department:id,name',
            ])
            ->when($name, fn(Builder $query) => $query->where('name', 'like', "%{$name}%"))
            ->orderBy($sort, $order)
            ->paginate($rows);

        return response_success([
            'rows'  => $query->items(),
            'total' => $query->total()
        ]);
    }

    /**
     * 新增治疗室
     * @param RoomRequest $request
     * @return JsonResponse
     */
    public function create(RoomRequest $request): JsonResponse
    {
        $room = Room::query()->create(
            $request->formData()
        );
        return response_success($room);
    }

    /**
     * 更新治疗室
     * @param RoomRequest $request
     * @return JsonResponse
     */
    public function update(RoomRequest $request): JsonResponse
    {
        $room = Room::query()->find($request->input('id'));
        $room->update(
            $request->formData()
        );
        return response_success($room);
    }

    /**
     * 删除治疗室
     * @param RoomRequest $request
     * @return JsonResponse
     */
    public function remove(RoomRequest $request): JsonResponse
    {
        Room::query()->find($request->input('id'))->delete();
        return response_success();
    }
}
