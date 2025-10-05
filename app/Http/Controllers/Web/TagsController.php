<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\TagsRequest;
use App\Models\Tags;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TagsController extends Controller
{
    /**
     * 列表
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $keyword = $request->input('keyword');
        $medium  = Tags::query()
            ->when($keyword, function (Builder $query) use ($keyword) {

                // 执行包含关键字的原始查询
                $matching_tree_raw = Tags::query()
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
     * (批量)创建标签
     * @param TagsRequest $request
     * @return JsonResponse
     */
    public function create(TagsRequest $request): JsonResponse
    {
        $data = Tags::query()->create(
            $request->formData()
        );
        return response_success($data);
    }

    /**
     * 更新标签信息
     * @param TagsRequest $request
     * @return JsonResponse
     */
    public function update(TagsRequest $request): JsonResponse
    {
        $tag = Tags::query()->find($request->input('id'));
        $tag->update(
            $request->formData()
        );
        return response_success($tag);
    }

    /**
     * 删除地区数据
     * @param TagsRequest $request
     * @return JsonResponse
     */
    public function remove(TagsRequest $request): JsonResponse
    {
        Tags::query()->find($request->input('id'))->delete();
        return response_success();
    }
}
