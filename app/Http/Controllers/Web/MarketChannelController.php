<?php

namespace App\Http\Controllers\Web;

use App\Helpers\Attachment;
use App\Http\Controllers\Controller;
use App\Http\Requests\MarketChannel\CreateRequest;
use App\Http\Requests\MarketChannel\RemoveRequest;
use App\Http\Requests\MarketChannel\UpdateRequest;
use App\Models\Medium;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
            ->where(fn($query) => $query->where('tree', 'like', "0-1-4-%")->orWhere('id', 4))
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
            ->orderBy('id')
            ->get();

        return response_success(
            list_to_tree($data->toArray(), 'id', 'parentid', 'children', 1)
        );
    }

    /**
     * 所有渠道
     * @return JsonResponse
     */
    public function tree(): JsonResponse
    {
        $data = Medium::query()
            ->select(['id', 'name as text', 'parentid', 'child'])
            ->where('tree', 'like', "0-1-4-%")
            ->orWhere('id', 4)
            ->orderBy('id')
            ->get();
        return response_success($data);
    }

    /**
     * 创建渠道
     * @param CreateRequest $request
     * @return JsonResponse
     */
    public function create(CreateRequest $request): JsonResponse
    {
        $medium = Medium::query()->create(
            $request->formData()
        );
        $medium->attachments()->createMany(
            $request->attachmentData($medium->id)
        );
        return response_success($medium);
    }

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
     * @param UpdateRequest $request
     * @return JsonResponse
     */
    public function update(UpdateRequest $request): JsonResponse
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
     * @param RemoveRequest $request
     * @return JsonResponse
     */
    public function remove(RemoveRequest $request): JsonResponse
    {
        Medium::query()->where('id', $request->input('id'))->delete();
        return response_success();
    }
}
