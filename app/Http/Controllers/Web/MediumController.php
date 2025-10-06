<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\MediumRequest;
use App\Models\Medium;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MediumController extends Controller
{
    /**
     * 加载列表
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $keyword = $request->input('keyword');
        $medium  = Medium::query()
            ->with([
                'createUser:id,name',
            ])
            ->when($keyword, function (Builder $query) use ($keyword) {

                // 执行包含关键字的原始查询
                $matching_tree_raw = Medium::query()
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
            ->orderBy('order')
            ->orderBy('id')
            ->get();
        return response_success($medium);
    }

    /**
     * 创建媒介来源
     * @param MediumRequest $request
     * @return JsonResponse
     */
    public function create(MediumRequest $request): JsonResponse
    {
        $formData = $request->formData();

        // 批量创建
        $createdRecords = [];
        foreach ($formData as $data) {
            $createdRecords[] = Medium::query()->create($data);
        }

        return response_success($createdRecords);
    }

    /**
     * 更新媒介来源
     * @param MediumRequest $request
     * @return JsonResponse
     */
    public function update(MediumRequest $request): JsonResponse
    {
        $medium = Medium::query()->find(
            $request->input('id')
        );
        $medium->update(
            $request->formData()
        );
        return response_success($medium);
    }

    /**
     * 删除媒介来源
     * @param MediumRequest $request
     * @return JsonResponse
     */
    public function remove(MediumRequest $request): JsonResponse
    {
        Medium::query()->find($request->input('id'))->delete();
        return response_success();
    }

    /**
     * 交换媒介来源顺序
     * @param MediumRequest $request
     * @return JsonResponse
     */
    public function swap(MediumRequest $request): JsonResponse
    {
        $id1 = $request->input('id1');
        $id2 = $request->input('id2');
        $position = $request->input('position');

        $medium1 = Medium::query()->find($id1);
        $medium2 = Medium::query()->find($id2);

        // 获取目标记录的顺序值
        $targetOrder = $medium2->order;

        if ($position === 'bottom') {
            // 移动到目标记录下方
            Medium::query()
                ->where('order', '>', $targetOrder)
                ->increment('order');

            $medium1->update(['order' => $targetOrder + 1]);
        } else {
            // 移动到目标记录上方
            Medium::query()
                ->where('order', '<', $targetOrder)
                ->decrement('order');

            $medium1->update(['order' => $targetOrder - 1]);
        }

        return response_success();
    }
}
