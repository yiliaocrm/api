<?php

namespace App\Http\Controllers\Web;

use App\Exports\ProductExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\ProductRequest;
use App\Models\Product;
use App\Models\ProductType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /**
     * 收费项目列表
     */
    public function manage(ProductRequest $request): JsonResponse
    {
        $sort = $request->input('sort', 'id');
        $rows = $request->input('rows', 10);
        $order = $request->input('order', 'desc');
        $type_id = $request->input('type_id');
        $keyword = $request->input('keyword');
        $query = Product::query()
            ->with([
                'type:id,name',
                'department:id,name',
                'expenseCategory:id,name',
                'deductDepartmentRelation:id,name',
            ])
            ->whereIn('type_id', ProductType::query()->find($type_id)->getAllChild()->pluck('id'))
            ->when($keyword, fn (Builder $query) => $query->where('keyword', 'like', '%'.$keyword.'%'))
            ->orderBy($sort, $order)
            ->paginate($rows);

        return response_success([
            'rows' => $query->items(),
            'total' => $query->total(),
        ]);
    }

    /**
     * 创建收费项目
     */
    public function create(ProductRequest $request): JsonResponse
    {
        $data = Product::query()->create(
            $request->formData()
        );

        return response_success($data);
    }

    /**
     * 更新产品信息
     */
    public function update(ProductRequest $request): JsonResponse
    {
        $product = Product::query()->find(
            $request->input('id')
        );
        $product->update(
            $request->formData()
        );

        return response_success();
    }

    /**
     * 批量删除项目
     */
    public function remove(ProductRequest $request): JsonResponse
    {
        Product::query()
            ->whereIn('id', $request->input('id'))
            ->delete();

        return response_success();
    }

    /**
     * 批量更新
     */
    public function batch(ProductRequest $request): JsonResponse
    {
        Product::query()
            ->whereIn('id', $request->input('ids'))
            ->update($request->batchForm());

        return response_success();
    }

    /**
     * 导出excel
     */
    public function export(): ProductExport
    {
        return new ProductExport;
    }

    /**
     * 查询收费项目
     */
    public function query(Request $request): JsonResponse
    {
        $type_id = $request->input('type_id', 1);
        $rows = $request->input('rows', 10);
        $query = Product::query()
            ->where('disabled', 0)
            ->whereIn('type_id', ProductType::query()->find($type_id)->getAllChild()->pluck('id'))
            ->when($request->input('keyword'), fn ($query) => $query->where('keyword', 'like', '%'.$request->input('keyword').'%'))
            ->orderBy('id', 'desc')
            ->paginate($rows);

        return response_success([
            'rows' => $query->items(),
            'total' => $query->total(),
        ]);
    }

    /**
     * combogrid 查询
     */
    public function combogrid(Request $request): JsonResponse
    {
        $rows = request('rows', 10);
        $query = Product::query()
            ->when($request->input('id'), function ($query) use ($request) {
                return $query->where('id', $request->input('id'));
            })
            ->when(! $request->input('id'), function ($query) use ($request) {
                return $query->where('keyword', 'like', '%'.$request->input('q').'%')
                    ->where('disabled', 0)
                    ->orderBy('id', 'desc');
            })
            ->paginate($rows);

        return response_success([
            'rows' => $query->items(),
            'total' => $query->total(),
        ]);
    }

    /**
     * 启用收费项目
     */
    public function enable(ProductRequest $request): JsonResponse
    {
        $product = Product::query()->find($request->input('id'));
        $product->update([
            'disabled' => 0,
        ]);

        return response_success($product);
    }

    /**
     * 禁用收费项目
     */
    public function disable(ProductRequest $request): JsonResponse
    {
        $product = Product::query()->find($request->input('id'));
        $product->update([
            'disabled' => 1,
        ]);

        return response_success($product);
    }
}
