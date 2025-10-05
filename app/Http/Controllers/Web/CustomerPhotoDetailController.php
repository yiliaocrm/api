<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\CustomerPhotoDetail\DownloadRequest;
use App\Http\Requests\CustomerPhotoDetail\RemoveRequest;
use App\Http\Requests\CustomerPhotoDetail\RenameRequest;
use App\Models\CustomerPhotoDetail;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CustomerPhotoDetailController extends Controller
{
    /**
     * 照片重命名
     * @param RenameRequest $request
     * @return JsonResponse
     */
    public function rename(RenameRequest $request): JsonResponse
    {
        $detail = CustomerPhotoDetail::query()->find(
            $request->input('id')
        );

        $detail->update([
            'name' => $request->input('name')
        ]);

        return response_success($detail);
    }

    /**
     * 删除照片
     * @param RemoveRequest $request
     * @return JsonResponse
     */
    public function remove(RemoveRequest $request): JsonResponse
    {
        $detail = CustomerPhotoDetail::query()->find(
            $request->input('id')
        );
        $detail->delete();
        return response_success();
    }

    /**
     * 下载照片
     * @param DownloadRequest $request
     * @return StreamedResponse
     */
    public function download(DownloadRequest $request): StreamedResponse
    {
        $detail = CustomerPhotoDetail::query()->find(
            $request->input('id')
        );

        // 写入日志
        $detail->customerLog()->create([
            'customer_id' => $detail->customer_id,
            'original'    => $detail->getRawOriginal()
        ]);

        return Storage::disk(config('filesystems.default'))->download(
            $detail->getRawOriginal('file_path')
        );
    }
}
