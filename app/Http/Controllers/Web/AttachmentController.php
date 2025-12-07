<?php

namespace App\Http\Controllers\Web;

use App\Models\Attachment;
use App\Models\AttachmentGroup;
use App\Helpers\AttachmentHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\AttachmentRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Database\Eloquent\Builder;

class AttachmentController extends Controller
{
    /**
     * 附件列表
     * @param AttachmentRequest $request
     * @return JsonResponse
     */
    public function index(AttachmentRequest $request): JsonResponse
    {
        $rows    = $request->input('rows', 10);
        $sort    = $request->input('sort', 'id');
        $order   = $request->input('order', 'desc');
        $keyword = $request->input('keyword');

        $query = Attachment::query()
            ->when($keyword, fn(Builder $q) => $q->where('file_name', 'like', "%{$keyword}%"))
            ->orderBy($sort, $order)
            ->paginate($rows);

        return response_success([
            'rows'  => $query->items(),
            'total' => $query->total()
        ]);
    }

    /**
     * 前端浏览器选择器带分页
     * @param AttachmentRequest $request
     * @return JsonResponse
     */
    public function picker(AttachmentRequest $request): JsonResponse
    {
        $rows    = $request->input('rows', 10);
        $sort    = $request->input('sort', 'id');
        $order   = $request->input('order', 'desc');
        $keyword = $request->input('keyword');
        $groupId = $request->input('group_id');

        $query = Attachment::query()
            ->when($keyword, fn(Builder $q) => $q->where('file_name', 'like', "%{$keyword}%"))
//            ->when($groupId, fn(Builder $q) => $q->where('group_id', $groupId))
            ->orderBy($sort, $order)
            ->paginate($rows);

        return response_success([
            'rows'  => $query->items(),
            'total' => $query->total()
        ]);
    }

    /**
     * 检查文件MD5
     * @param AttachmentRequest $request
     * @return JsonResponse
     */
    public function check(AttachmentRequest $request): JsonResponse
    {
        $attachment = Attachment::query()
            ->with(['thumbnails'])
            ->where('file_md5', $request->input('md5'))
            ->first();
        return response_success($attachment);
    }

    /**
     * 上传文件
     * @param AttachmentRequest $request
     * @param AttachmentHelper $service
     * @return JsonResponse
     */
    public function upload(AttachmentRequest $request, AttachmentHelper $service): JsonResponse
    {
        $file = $request->file('file');
        $md5  = md5_file($file->getRealPath());

        // 检测文件是否已存在
        $attachment = Attachment::query()->with(['thumbnails'])->where('file_md5', $md5)->first();
        if ($attachment) {
            return response_success($attachment);
        }

        // 上传文件
        $attachment = Attachment::create([
            ...$service->upload($file, 'attachments'),
            'group_id' => $request->input('group_id') ?: null,
        ]);

        // 如果是图片 且需要生成缩略图
        if ($request->filled('thumb') && $attachment->is_image) {
            $thumbData = $service->makeImageThumb($file, 'attachments');
            $attachment->thumbnails()->create($thumbData);
            $attachment->load(['thumbnails']);
        }

        return response_success($attachment);
    }

    /**
     * 删��文件
     * @param AttachmentRequest $request
     * @return JsonResponse
     */
    public function remove(AttachmentRequest $request): JsonResponse
    {
        Attachment::query()->find($request->input('id'))->delete();
        return response_success(msg: '删除成功');
    }

    /**
     * 获取附件分组
     * @return JsonResponse
     */
    public function groups(): JsonResponse
    {
        $groups = AttachmentGroup::query()
            ->where('parent_id', 0)
            ->orderBy('order')
            ->get();
        return response_success($groups);
    }

    /**
     * 创建附件分组
     * @param AttachmentRequest $request
     * @return JsonResponse
     */
    public function createGroup(AttachmentRequest $request): JsonResponse
    {
        $group = AttachmentGroup::create([
            'name'      => $request->input('name'),
            'parent_id' => $request->input('parent_id'),
            'order'     => $request->input('order', 0),
            'system'    => 0,
        ]);

        return response_success($group, '创建成功');
    }

    /**
     * 更新附件分组
     * @param AttachmentRequest $request
     * @return JsonResponse
     */
    public function updateGroup(AttachmentRequest $request): JsonResponse
    {
        $group = AttachmentGroup::query()->find($request->input('id'));

        $group->update([
            'name'      => $request->input('name'),
            'parent_id' => $request->input('parent_id'),
            'order'     => $request->input('order', 0),
        ]);

        return response_success($group, '更新成功');
    }

    /**
     * 删除附件分组
     * @param AttachmentRequest $request
     * @return JsonResponse
     */
    public function removeGroup(AttachmentRequest $request): JsonResponse
    {
        AttachmentGroup::query()->find($request->input('id'))->delete();
        return response_success(msg: '删除成功');
    }
}
