<?php

namespace App\Http\Controllers\Web;

use Carbon\Carbon;
use App\Models\Supplier;
use App\Exports\SupplierExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\SupplierRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Database\Eloquent\Builder;

class SupplierController extends Controller
{
    /**
     * 供应商列表
     * @param Request $request
     * @return JsonResponse
     */
    public function manage(Request $request): JsonResponse
    {
        $sort  = $request->input('sort', 'id');
        $order = $request->input('order', 'desc');
        $rows  = $request->input('rows', 10);
        $query = Supplier::query()
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
            'total' => $query->total()
        ]);
    }

    /**
     * 查看供应商信息
     * @param SupplierRequest $request
     * @return JsonResponse
     */
    public function info(SupplierRequest $request): JsonResponse
    {
        $supplier = Supplier::query()->find(
            $request->input('id')
        );
        return response_success($supplier);
    }

    /**
     * 创建供应商
     * @param SupplierRequest $request
     * @return JsonResponse
     */
    public function create(SupplierRequest $request): JsonResponse
    {
        $supplier = Supplier::query()->create(
            $request->formData()
        );
        return response_success($supplier);
    }

    /**
     * 更新供应商
     * @param SupplierRequest $request
     * @return JsonResponse
     */
    public function update(SupplierRequest $request): JsonResponse
    {
        $supplier = Supplier::query()->find(
            $request->input('id')
        );
        $supplier->update(
            $request->formData()
        );
        return response_success($supplier);
    }

    /**
     * 删除供应商
     * @param SupplierRequest $request
     * @return JsonResponse
     */
    public function remove(SupplierRequest $request): JsonResponse
    {
        Supplier::query()->find($request->input('id'))->delete();
        return response_success();
    }

    /**
     * 启用
     * @param SupplierRequest $request
     * @return JsonResponse
     */
    public function enable(SupplierRequest $request): JsonResponse
    {
        $supplier = Supplier::query()->find(
            $request->input('id')
        );
        $supplier->update([
            'disabled' => 0
        ]);
        return response_success($supplier);
    }

    /**
     * 停用
     * @param SupplierRequest $request
     * @return JsonResponse
     */
    public function disable(SupplierRequest $request): JsonResponse
    {
        $supplier = Supplier::query()->find(
            $request->input('id')
        );
        $supplier->update([
            'disabled' => 1
        ]);
        return response_success($supplier);
    }

    /**
     * 查询（combogrid）
     * @param Request $request
     * @return JsonResponse
     */
    public function query(Request $request): JsonResponse
    {
        $rows  = $request->input('rows', 10);
        $query = Supplier::query()
            ->when($keyword = $request->input('keyword'), fn(Builder $query) => $query->whereLike('keyword', "%{$keyword}%"))
            ->when($id = $request->input('id'), fn(Builder $query) => $query->where('id', $id))
            ->orderBy('id', 'desc')
            ->paginate($rows);

        return response_success([
            'rows'  => $query->items(),
            'total' => $query->total()
        ]);
    }

    /**
     * 导出[供应厂商]
     * @param Request $request
     * @return SupplierExport
     */
    public function export(Request $request): SupplierExport
    {
        return new SupplierExport($request);
    }
}
