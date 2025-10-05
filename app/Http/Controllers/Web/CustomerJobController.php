<?php

namespace App\Http\Controllers\Web;

use App\Models\CustomerJob;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\CustomerJobRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Database\Eloquent\Builder;

class CustomerJobController extends Controller
{
    /**
     * 职业列表
     * @param Request $request
     * @return JsonResponse
     */
    public function manage(Request $request): JsonResponse
    {
        $rows  = $request->input('rows', 10);
        $sort  = $request->input('sort', 'id');
        $order = $request->input('order', 'desc');
        $query = CustomerJob::query()
            ->when($name = $request->input('name'), fn(Builder $query) => $query->where('name', 'like', "%{$name}%"))
            ->orderBy($sort, $order)
            ->paginate($rows);

        return response_success([
            'rows'  => $query->items(),
            'total' => $query->total(),
        ]);
    }

    /**
     * 创建职业
     * @param CustomerJobRequest $request
     * @return JsonResponse
     */
    public function create(CustomerJobRequest $request)
    {
        $data = CustomerJob::query()->create(
            $request->formData()
        );
        return response_success($data);
    }

    /**
     * 更新职业
     * @param CustomerJobRequest $request
     * @return JsonResponse
     */
    public function update(CustomerJobRequest $request)
    {
        $data = CustomerJob::query()->find(
            $request->input('id')
        );
        $data->update($request->formData());
        return response_success($data);
    }

    /**
     * 删除职业信息
     * @param CustomerJobRequest $request
     * @return JsonResponse
     */
    public function remove(CustomerJobRequest $request)
    {
        CustomerJob::query()->find($request->input('id'))->delete();
        return response_success();
    }
}
