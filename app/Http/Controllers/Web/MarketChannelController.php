<?php

namespace App\Http\Controllers\Web;

use App\Models\Medium;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\MarketChannelRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Database\Eloquent\Builder;

class MarketChannelController extends Controller
{
    /**
     * 首页
     * @param MarketChannelRequest $request
     * @return JsonResponse
     */
    public function index(MarketChannelRequest $request): JsonResponse
    {
        $keyword = $request->input('keyword');
        $data    = Medium::query()
            ->with(['user:id,name'])
            ->where(fn($query) => $query->where('tree', 'like', "0-4-%"))
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

        return response_success($data);
    }

    /**
     * 所有渠道
     * @return JsonResponse
     */
    public function tree(): JsonResponse
    {
        $data = Medium::query()
            ->select(['id', 'name', 'parentid', 'child'])
            ->where('tree', 'like', "0-4-%")
            ->orderBy('id')
            ->get();
        return response_success(list_to_tree(list: $data->toArray(), root: 4));
    }

    /**
     * 创建渠道
     * @param MarketChannelRequest $request
     * @return JsonResponse
     */
    public function create(MarketChannelRequest $request): JsonResponse
    {
        $medium = Medium::query()->create(
            $request->formData()
        );

        // 写入 attachment_uses 多态关联表（引用计数由 AttachmentUse 模型事件自动维护）
        $attachmentIds = $request->attachmentData();
        if (!empty($attachmentIds)) {
            $medium->attachments()->attach($attachmentIds);
        }

        $medium->load(['user:id,name']);
        return response_success($medium);
    }

    /**
     * 渠道详情
     * @param MarketChannelRequest $request
     * @return JsonResponse
     */
    public function info(MarketChannelRequest $request): JsonResponse
    {
        $medium = Medium::query()->find(
            $request->input('id')
        );
        $medium->load(['attachments']);
        return response_success($medium);
    }

    /**
     * 更新渠道
     * @param MarketChannelRequest $request
     * @return JsonResponse
     */
    public function update(MarketChannelRequest $request): JsonResponse
    {
        $medium = Medium::query()->find(
            $request->input('id')
        );

        $medium->update(
            $request->formData()
        );

        // 同步更新 attachment_uses 多态关联表（引用计数由 AttachmentUse 模型事件自动维护）
        $medium->attachments()->sync($request->attachmentData());

        $medium->load(['user:id,name']);

        return response_success($medium);
    }

    /**
     * 删除渠道
     * @param MarketChannelRequest $request
     * @return JsonResponse
     */
    public function remove(MarketChannelRequest $request): JsonResponse
    {
        Medium::query()->where('id', $request->input('id'))->delete();
        return response_success();
    }

    /**
     * 交换渠道顺序
     * @param MarketChannelRequest $request
     * @return JsonResponse
     */
    public function swap(MarketChannelRequest $request): JsonResponse
    {
        $id1      = $request->input('id1');
        $id2      = $request->input('id2');
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
