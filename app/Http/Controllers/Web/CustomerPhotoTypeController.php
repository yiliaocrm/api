<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\CustomerPhotoTypeRequest;
use App\Models\CustomerPhotoType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerPhotoTypeController extends Controller
{
    /**
     * 列表页
     */
    public function index(Request $request): JsonResponse
    {
        $rows = $request->input('rows', 10);
        $sort = $request->input('sort', 'id');
        $order = $request->input('order', 'desc');
        $query = CustomerPhotoType::query()
            ->when($name = $request->input('name'), fn (Builder $query) => $query->where('name', 'like', "%{$name}%"))
            ->orderBy($sort, $order)
            ->paginate($rows);

        return response_success([
            'rows' => $query->items(),
            'total' => $query->total(),
        ]);
    }

    /**
     * 创建照片类型
     */
    public function create(CustomerPhotoTypeRequest $request): JsonResponse
    {
        $type = CustomerPhotoType::query()->create(
            $request->only('name', 'remark')
        );

        return response_success($type);
    }

    /**
     * 获取照片类型信息
     */
    public function info(CustomerPhotoTypeRequest $request): JsonResponse
    {
        $type = CustomerPhotoType::query()->find(
            $request->input('id')
        );

        return response_success($type);
    }

    /**
     * 更新照片类型
     */
    public function update(CustomerPhotoTypeRequest $request): JsonResponse
    {
        $type = CustomerPhotoType::query()->find(
            $request->input('id')
        );
        $type->update(
            $request->only('name', 'remark')
        );

        return response_success($type);
    }

    /**
     * 删除照片类型
     */
    public function remove(CustomerPhotoTypeRequest $request): JsonResponse
    {
        CustomerPhotoType::find($request->input('id'))->delete();

        return response_success(msg: '删除成功!');
    }
}
