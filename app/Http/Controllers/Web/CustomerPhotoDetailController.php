<?php

namespace App\Http\Controllers\Web;

use App\Models\CustomerPhotoDetail;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\CustomerPhotoDetailRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CustomerPhotoDetailController extends Controller
{
    /**
     * 照片重命名
     * @param CustomerPhotoDetailRequest $request
     * @return JsonResponse
     */
    public function rename(CustomerPhotoDetailRequest $request): JsonResponse
    {
        $detail = CustomerPhotoDetail::query()->find(
            $request->input('id')
        );

        $detail->update($request->formData());

        return response_success($detail);
    }

    /**
     * 删除照片
     * @param CustomerPhotoDetailRequest $request
     * @return JsonResponse
     */
    public function remove(CustomerPhotoDetailRequest $request): JsonResponse
    {
        $detail = CustomerPhotoDetail::query()->find(
            $request->input('id')
        );
        $detail->delete();
        return response_success();
    }

    /**
     * 下载照片
     * @param CustomerPhotoDetailRequest $request
     * @return StreamedResponse
     */
    public function download(CustomerPhotoDetailRequest $request): StreamedResponse
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
