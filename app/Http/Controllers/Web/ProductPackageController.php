<?php

namespace App\Http\Controllers\Web;

use Exception;
use Throwable;
use App\Models\ProductPackage;
use App\Models\ProductPackageType;
use App\Exceptions\HisException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\ProductPackageRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Builder;

class ProductPackageController extends Controller
{
    /**
     * 套餐管理
     * @param Request $request
     * @return JsonResponse
     */
    public function manage(Request $request): JsonResponse
    {
        $sort    = $request->input('sort', 'id');
        $rows    = $request->input('rows', 10);
        $order   = $request->input('order', 'desc');
        $type_id = $request->input('type_id');
        $keyword = $request->input('keyword');
        $query   = ProductPackage::query()
            ->with([
                'type',
                'user',
                'details.unit:id,name',
                'details.product',
                'details.department:id,name',
                'details.goods.units'
            ])
            ->when($keyword, fn(Builder $query) => $query->whereLike('keyword', '%' . $keyword . '%'))
            ->when($type_id, fn(Builder $query) => $query->whereIn('type_id', ProductPackageType::query()->find($type_id)->getAllChild()->pluck('id')))
            ->orderBy($sort, $order)
            ->paginate($rows);

        return response_success([
            'rows'  => $query->items(),
            'total' => $query->total()
        ]);
    }

    /**
     * 创建套餐
     * @param ProductPackageRequest $request
     * @return JsonResponse
     * @throws HisException|Throwable
     */
    public function create(ProductPackageRequest $request): JsonResponse
    {
        DB::beginTransaction();
        try {

            $package = ProductPackage::query()->create(
                $request->formData()
            );
            $package->details()->createMany(
                $request->detailsData()
            );

            DB::commit();
            return response_success($package);
        } catch (Exception $e) {
            DB::rollBack();
            throw new HisException($e->getMessage());
        }
    }

    /**
     * 更新套餐
     * @param ProductPackageRequest $request
     * @return JsonResponse
     * @throws HisException|Throwable
     */
    public function update(ProductPackageRequest $request): JsonResponse
    {
        DB::beginTransaction();
        try {

            $package = ProductPackage::query()->find(
                $request->input('form.id')
            );

            // 更新
            $package->update($request->formData());

            // 删除旧的
            $package->details()->delete();

            // 添加新的
            $package->details()->createMany(
                $request->detailsData()
            );

            DB::commit();
            return response_success($package);
        } catch (Exception $e) {
            DB::rollBack();
            throw new HisException($e->getMessage());
        }
    }

    /**
     * 删除套餐
     * @param ProductPackageRequest $request
     * @return JsonResponse
     */
    public function remove(ProductPackageRequest $request): JsonResponse
    {
        ProductPackage::query()->find($request->input('id'))->delete();
        return response_success();
    }

    /**
     * 开单-选择套餐
     * @param ProductPackageRequest $request
     * @return JsonResponse
     */
    public function choose(ProductPackageRequest $request): JsonResponse
    {
        $sort    = $request->input('sort', 'id');
        $rows    = $request->input('rows', 10);
        $order   = $request->input('order', 'desc');
        $type_id = $request->input('type_id');
        $keyword = $request->input('keyword');
        $query   = ProductPackage::query()
            ->with('type', 'details.product', 'details.goods.units')
            ->where('disabled', 0)
            // 关键词查询
            ->when($keyword, function ($query) use ($keyword) {
                $query->where('keyword', 'like', '%' . $keyword . '%');
            })
            // 分类
            ->when($type_id, function ($query) use ($type_id) {
                $query->whereIn('type_id', ProductPackageType::query()->find($type_id)->getAllChild()->pluck('id'));
            })
            ->orderBy($sort, $order)
            ->paginate($rows);

        return response_success([
            'rows'  => $query->items(),
            'total' => $query->total()
        ]);
    }
}
