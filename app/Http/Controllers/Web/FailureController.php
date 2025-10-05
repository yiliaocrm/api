<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\FailureRequest;
use App\Models\Failure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FailureController extends Controller
{
    /**
     * 列表
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $keyword = $request->input('keyword');
        $medium  = Failure::query()
            ->when($keyword, function (Builder $query) use ($keyword) {

                // 执行包含关键字的原始查询
                $matching_tree_raw = Failure::query()
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
     * 创建数据
     * @param FailureRequest $request
     * @return JsonResponse
     */
    public function create(FailureRequest $request)
    {
        $data = Failure::query()->create(
            $request->formData()
        );
        return response_success($data);
    }

    /**
     * 更新数据
     * @param FailureRequest $request
     * @return JsonResponse
     */
    public function update(FailureRequest $request)
    {
        $data = Failure::query()->find(
            $request->input('id')
        );
        $data->update(
            $request->formData()
        );
        return response_success($data);
    }

    /**
     * 删除地区数据
     * @param FailureRequest $request
     * @return JsonResponse
     */
    public function remove(FailureRequest $request)
    {
        Failure::query()->find($request->input('id'))->delete();
        return response_success();
    }
}
