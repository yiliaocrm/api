<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProductPackageType\CreateRequest;
use App\Http\Requests\ProductPackageType\UpdateRequest;
use App\Models\ProductPackage;
use App\Models\ProductPackageType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductPackageTypeController extends Controller
{
    /**
     * 所有分类
     * @return JsonResponse
     */
    public function all(): JsonResponse
    {
        $type = ProductPackageType::query()
            ->select('id', 'name AS text', 'parentid', 'child')
            ->orderByDesc('order')
            ->orderByDesc('id')
            ->get()
            ->toArray();
        return response_success(list_to_tree($type));
    }

    /**
     * 更新分类排序
     * @param Request $request
     * @return JsonResponse
     */
    public function sort(Request $request): JsonResponse
    {
        $node1 = ProductPackageType::query()->find(
            $request->input('id1')
        );
        $node2 = ProductPackageType::query()->find(
            $request->input('id2')
        );

        $order1 = $node1->order;
        $order2 = $node2->order;

        $node1->update(['order' => $order2]);
        $node2->update(['order' => $order1]);
        return response_success();
    }

    /**
     * 创建套餐分类
     * @param CreateRequest $request
     * @return JsonResponse
     */
    public function create(CreateRequest $request): JsonResponse
    {
        $data = ProductPackageType::query()->create(
            $request->formData()
        );
        return response_success($data);
    }

    /**
     * 更新分类名称
     * @param UpdateRequest $request
     * @return JsonResponse
     */
    public function update(UpdateRequest $request): JsonResponse
    {
        $data = ProductPackageType::query()->find(
            $request->input('id')
        );
        $data->update(
            $request->formData()
        );
        return response_success($data);
    }

    /**
     * 删除分类
     * @param Request $request
     * @return JsonResponse
     */
    public function remove(Request $request): JsonResponse
    {
        $id = $request->input('id');
        ProductPackage::query()->whereIn('type_id', ProductPackageType::query()->find($id)->getAllChild()->pluck('id'))->delete();
        ProductPackageType::query()->find($id)->delete();
        return response_success();
    }
}
