<?php

namespace App\Http\Controllers\Web;

use Exception;
use Throwable;
use App\Models\Item;
use App\Models\ProductType;
use App\Exceptions\HisException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Http\Requests\ProductTypeRequest;

class ProductTypeController extends Controller
{
    /**
     * 返回所有分类
     * @return JsonResponse
     */
    public function all(): JsonResponse
    {
        $type = ProductType::query()
            ->select(['id', 'name AS text', 'parentid', 'child', 'tree'])
            ->get()
            ->toArray();
        return response_success(list_to_tree($type));
    }

    /**
     * 创建分类
     * @param ProductTypeRequest $request
     * @return JsonResponse
     * @throws HisException|Throwable
     */
    public function create(ProductTypeRequest $request): JsonResponse
    {
        DB::beginTransaction();
        try {

            // 创建[收费项目分类]
            $type = ProductType::query()->create(
                $request->formData()
            );

            // 同步到[咨询项目]
            if (parameter('cywebos_enable_item_product_type_sync')) {
                Item::query()->create(
                    $request->formData()
                );
            }

            DB::commit();

            return response_success($type);
        } catch (Exception $e) {
            DB::rollBack();
            throw new HisException($e->getMessage());
        }
    }

    /**
     * 更新分类名称
     * @param ProductTypeRequest $request
     * @return JsonResponse
     * @throws HisException|Throwable
     */
    public function update(ProductTypeRequest $request): JsonResponse
    {
        DB::beginTransaction();
        try {
            // 更新[收费项目分类]
            $type = ProductType::query()->find($request->input('id'));
            $type->update(
                $request->formData()
            );

            // 同步更新[咨询项目]
            if (parameter('cywebos_enable_item_product_type_sync')) {
                Item::query()->find($request->input('id'))->update(
                    $request->formData()
                );
            }

            DB::commit();
            return response_success($type);
        } catch (Exception $e) {
            DB::rollBack();
            throw new HisException($e->getMessage());
        }

    }

    /**
     * 删除分类
     * @param ProductTypeRequest $request
     * @return JsonResponse
     * @throws HisException|Throwable
     */
    public function remove(ProductTypeRequest $request): JsonResponse
    {
        DB::beginTransaction();
        try {

            // 删除[收费项目分类]
            ProductType::query()->find($request->input('id'))->delete();

            // 同步删除[咨询项目]
            if (parameter('cywebos_enable_item_product_type_sync')) {
                Item::query()->find($request->input('id'))->delete();
            }

            DB::commit();

            return response_success();
        } catch (Exception $e) {
            DB::rollBack();
            throw new HisException($e->getMessage());
        }
    }

    /**
     * 移动节点
     * @param ProductTypeRequest $request
     * @return JsonResponse
     * @throws HisException|Throwable
     */
    public function move(ProductTypeRequest $request): JsonResponse
    {
        DB::beginTransaction();
        try {
            $type = ProductType::query()->find(
                $request->input('id')
            );
            $type->update([
                'parentid' => $request->input('parentid')
            ]);

            // 同步移动[咨询项目]
            if (parameter('cywebos_enable_item_product_type_sync')) {
                Item::query()->find($request->input('id'))->update([
                    'parentid' => $request->input('parentid')
                ]);
            }

            // 查询所有子节点返回
            $all = ProductType::query()
                ->select(['id', 'name AS text', 'parentid', 'child', 'tree'])
                ->where('tree', 'like', "{$type->tree}-%")
                ->orWhere('id', $type->id)
                ->orderBy('id', 'ASC')
                ->get()
                ->toArray();

            DB::commit();
            return response_success(list_to_tree($all, 'id', 'parentid', 'children', $type->parentid));
        } catch (Exception $e) {
            DB::rollBack();
            throw new HisException($e->getMessage());
        }
    }
}
