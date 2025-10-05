<?php

namespace App\Http\Controllers\Api;

use App\Helpers\Attachment;
use App\Models\CustomerPhoto;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CustomerPhotoInfoRequest;
use App\Http\Requests\Api\CustomerPhotoUploadRequest;
use App\Http\Requests\Api\CustomerPhotoCreateRequest;

class CustomerPhotoController extends Controller
{
    /**
     * 列表
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $rows  = request('rows', 10);
        $sort  = request('sort', 'customer_photos.created_at');
        $order = request('order', 'desc');
        $query = CustomerPhoto::query()
            ->select([
                'customer.sex',
                'customer.name as customer_name',
                'customer_photos.id',
                'customer_photos.flag',
                'customer_photos.title',
                'customer_photos.remark',
                'customer_photos.created_at',
                'customer_photos.create_user_id',
            ])
            ->with([
                'createUser:id,name'
            ])
            ->leftJoin('customer', 'customer.id', '=', 'customer_photos.customer_id')
            ->orderBy($sort, $order)
            ->paginate($rows);

        return response_success([
            'rows'  => $query->items(),
            'total' => $query->total()
        ]);
    }

    /**
     * 查看客户相册信息
     * @param CustomerPhotoInfoRequest $request
     * @return JsonResponse
     */
    public function info(CustomerPhotoInfoRequest $request): JsonResponse
    {
        $album = CustomerPhoto::query()->find(
            $request->input('id')
        );
        $album->load([
            'customer',
            'details'
        ]);
        return response_success($album);
    }

    /**
     * 上传照片
     * @param CustomerPhotoUploadRequest $request
     * @param Attachment $service
     * @return JsonResponse
     */
    public function upload(CustomerPhotoUploadRequest $request, Attachment $service): JsonResponse
    {
        $album = CustomerPhoto::query()->find(
            $request->input('id')
        );

        // 上传附件
        $attachment = $service->upload($request->file('file'), 'customer_photo');
        $thumbnail  = $service->makeImageThumb($request->file('file'), 'customer_photo');

        // 写入附件表
        $album->attachments()->createMany([
            $attachment,
            $thumbnail
        ]);

        // 写入相册明细
        $detail = $album->details()->create(
            $request->formData(
                $album,
                $attachment,
                $thumbnail
            )
        );

        return response_success($detail);
    }

    /**
     * 创建相册
     * @param CustomerPhotoCreateRequest $request
     * @return JsonResponse
     */
    public function create(CustomerPhotoCreateRequest $request): JsonResponse
    {
        $album = CustomerPhoto::query()->create(
            $request->formData()
        );
        return response_success($album);
    }
}
