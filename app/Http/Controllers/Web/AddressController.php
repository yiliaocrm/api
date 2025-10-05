<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\AddressRequest;
use App\Models\Address;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AddressController extends Controller
{
    /**
     * 地区管理
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $keyword = $request->input('keyword');
        $medium  = Address::query()
            ->when($keyword, function (Builder $query) use ($keyword) {

                // 执行包含关键字的原始查询
                $matching_tree_raw = Address::query()
                    ->selectRaw("REPLACE(tree, '-', ',') AS tree")
                    ->where('keyword', 'LIKE', '%' . $keyword . '%')
                    ->get();

                // 从上述查询结果中提取 ID
                $ids = [];
                foreach ($matching_tree_raw as $item) {
                    $ids = array_merge($ids, explode(',', $item->tree));
                }

                // 将提取的 ID 添加到主查询的 whereIn 子句中
                return $query->whereIn('id', array_unique($ids));
            })
            ->orderBy('id')
            ->get();
        return response_success($medium);
    }

    /**
     * 创建地区
     * @param AddressRequest $request
     * @return JsonResponse
     */
    public function create(AddressRequest $request): JsonResponse
    {
        $address = Address::query()->create(
            $request->formData()
        );
        return response_success($address);
    }

    /**
     * 更新地区信息
     * @param AddressRequest $request
     * @return JsonResponse
     */
    public function update(AddressRequest $request)
    {
        $address = Address::query()->find(
            $request->input('id')
        );
        $address->update(
            $request->formData()
        );
        return response_success($address);
    }

    /**
     * 删除地区
     * @param AddressRequest $request
     * @return JsonResponse
     */
    public function remove(AddressRequest $request)
    {
        Address::query()->find($request->input('id'))->delete();
        return response_success();
    }
}
