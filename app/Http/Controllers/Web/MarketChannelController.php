<?php

namespace App\Http\Controllers\Web;

use App\Models\Medium;
use App\Helpers\Attachment;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\MarketChannelRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Database\Eloquent\Builder;

class MarketChannelController extends Controller
{
    /**
     * 首页
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
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
        $medium->attachments()->createMany(
            $request->attachmentData($medium->id)
        );
        $medium->load(['user:id,name']);
        return response_success($medium);
    }

    /**
     * 渠道详情
     * @param Request $request
     * @return JsonResponse
     */
    public function info(Request $request): JsonResponse
    {
        $medium = Medium::query()->find(
            $request->input('id')
        );
        $medium->loadMissing(['attachments']);
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

        // 更新附件
        $medium->attachments()->delete();
        $medium->attachments()->createMany(
            $request->attachmentData($medium->id)
        );

        $medium->load(['user:id,name']);

        return response_success($medium);
    }

    /**
     * 上传渠道图片
     * @param Request $request
     * @param Attachment $attachment
     * @return JsonResponse
     */
    public function upload(Request $request, Attachment $attachment): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:jpeg,png,jpg',
        ], [
            'file.required' => '请选择上传文件',
            'file.file'     => '上传文件必须是图片',
            'file.mimes'    => '上传文件类型不符合要求',
        ]);

        $file  = $attachment->upload($request->file('file'), 'market-channel');
        $thumb = $attachment->makeImageThumb($request->file('file'), 'market-channel', 150, 150);

        return response_success([
            'name'      => $file['file_name'],
            'thumb'     => get_attachment_url($thumb['file_path']),
            'file_path' => get_attachment_url($file['file_path']),
            'file_mime' => $file['file_mime'],
        ]);
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
