<?php

namespace App\Http\Controllers\Web;

use App\Models\Manufacturer;
use App\Exports\ManufacturerExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\ManufacturerRequest;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Database\Eloquent\Builder;

class ManufacturerController extends Controller
{
    /**
     * 生产厂家管理页面
     * @param Request $request
     * @return JsonResponse
     */
    public function manage(Request $request): JsonResponse
    {
        $order = $request->input('order', 'desc');
        $sort  = $request->input('sort', 'id');
        $rows  = $request->input('rows', 10);
        $query = Manufacturer::query()
            ->when($request->input('created_at_start') && $request->input('created_at_end'), function ($query) use ($request) {
                $query->whereBetween('created_at', [
                    Carbon::parse($request->input('created_at_start')),
                    Carbon::parse($request->input('created_at_end'))->endOfDay()
                ]);
            })
            ->when($keyword = $request->input('keyword'), fn(Builder $query) => $query->whereLike('keyword', "%{$keyword}%"))
            ->orderBy($sort, $order)
            ->paginate($rows);

        return response_success([
            'rows'  => $query->items(),
            'total' => $query->total(),
        ]);
    }

    /**
     * 查看信息
     * @param ManufacturerRequest $request
     * @return JsonResponse
     */
    public function info(ManufacturerRequest $request): JsonResponse
    {
        $manufacturer = Manufacturer::find($request->input('id'));
        return response_success($manufacturer);
    }

    /**
     * 创建厂家
     * @param ManufacturerRequest $request
     * @return JsonResponse
     */
    public function create(ManufacturerRequest $request): JsonResponse
    {
        $manufacturer = Manufacturer::create(
            $request->formData()
        );
        return response_success($manufacturer);
    }

    /**
     * 更新厂家信息
     * @param ManufacturerRequest $request
     * @return JsonResponse
     */
    public function update(ManufacturerRequest $request): JsonResponse
    {
        $manufacturer = Manufacturer::find($request->input('id'));
        $manufacturer->update(
            $request->formData()
        );
        return response_success($manufacturer);
    }

    /**
     * 删除厂家
     * @param ManufacturerRequest $request
     * @return JsonResponse
     */
    public function remove(ManufacturerRequest $request): JsonResponse
    {
        Manufacturer::find($request->input('id'))->delete();
        return response_success();
    }

    /**
     * 开启
     * @param ManufacturerRequest $request
     * @return JsonResponse
     */
    public function enable(ManufacturerRequest $request): JsonResponse
    {
        $manufacturer = Manufacturer::find($request->input('id'));
        $manufacturer->update([
            'disabled' => 0
        ]);
        return response_success($manufacturer);
    }

    /**
     * 禁用
     * @param ManufacturerRequest $request
     * @return JsonResponse
     */
    public function disable(ManufacturerRequest $request): JsonResponse
    {
        $manufacturer = Manufacturer::find($request->input('id'));
        $manufacturer->update([
            'disabled' => 1
        ]);
        return response_success($manufacturer);
    }

    /**
     * combogrid查询
     * @param Request $request
     * @return JsonResponse
     */
    public function combogrid(Request $request): JsonResponse
    {
        $rows  = $request->input('rows', 10);
        $query = Manufacturer::query()
            ->when($request->input('keyword'), fn(Builder $query) => $query->where('keyword', 'like', '%' . $request->input('keyword') . '%'))
            ->when($request->input('id'), fn(Builder $query) => $query->where('id', $request->input('id')))
            ->orderBy('id', 'desc')
            ->paginate($rows);

        return response_success([
            'rows'  => $query->items(),
            'total' => $query->total()
        ]);
    }

    /**
     * 导出生产厂家命大
     * @param Request $request
     * @return ManufacturerExport
     */
    public function export(Request $request): ManufacturerExport
    {
        return new ManufacturerExport($request);
    }
}
