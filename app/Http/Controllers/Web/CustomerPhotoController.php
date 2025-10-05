<?php

namespace App\Http\Controllers\Web;

use App\Helpers\Attachment;
use App\Http\Controllers\Controller;
use App\Http\Requests\CustomerPhoto\CreateRequest;
use App\Http\Requests\CustomerPhoto\RemoveRequest;
use App\Http\Requests\CustomerPhoto\UpdateRequest;
use App\Http\Requests\CustomerPhoto\UploadRequest;
use App\Models\CustomerPhoto;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerPhotoController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $sort  = $request->input('sort', 'customer_photos.created_at');
        $order = $request->input('order', 'desc');
        $rows  = $request->input('rows', 10);
        $query = CustomerPhoto::query()
            ->with([
                'customer:id,idcard,name',
                'createUser:id,name',
            ])
            ->withCount('details')
            ->when($request->input('created_at_start') && $request->input('created_at_end'), function (Builder $query) use ($request) {
                $query->whereBetween('created_at', [
                    Carbon::parse($request->input('created_at_start')),
                    Carbon::parse($request->input('created_at_end'))->endOfDay()
                ]);
            })
            ->when($request->input('keyword'), function (Builder $query) use ($request) {
                $query->leftJoin('customer', 'customer.id', '=', 'customer_photos.customer_id')
                    ->where('customer.keyword', 'like', "%{$request->input('keyword')}%");
            })
            ->when($request->input('title'), function (Builder $query) use ($request) {
                $query->where('customer_photos.title', 'like', '%' . $request->input('title') . '%');
            })
            ->orderBy($sort, $order)
            ->paginate($rows);

        return response_success([
            'rows'  => $query->items(),
            'total' => $query->total()
        ]);
    }

    /**
     * 创建相册
     * @param CreateRequest $request
     * @return JsonResponse
     */
    public function create(CreateRequest $request): JsonResponse
    {
        $album = CustomerPhoto::query()->create(
            $request->formData()
        );
        $album->customerLog()->create([
            'customer_id' => $album->customer_id
        ]);
        $album->load(['details']);
        return response_success($album);
    }

    /**
     * 更新相册
     * @param UpdateRequest $request
     * @return JsonResponse
     */
    public function update(UpdateRequest $request): JsonResponse
    {
        $album = CustomerPhoto::query()->find(
            $request->input('id')
        );
        $album->update(
            $request->formData()
        );
        $album->customerLog()->create([
            'customer_id' => $album->customer_id
        ]);
        $album->load(['details']);
        return response_success($album);
    }

    /**
     * 上传对比照
     * @param UploadRequest $request
     * @param Attachment $service
     * @return JsonResponse
     */
    public function upload(UploadRequest $request, Attachment $service): JsonResponse
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
     * 删除相册
     * @param RemoveRequest $request
     * @return JsonResponse
     */
    public function remove(RemoveRequest $request): JsonResponse
    {
        $album = CustomerPhoto::query()->find(
            $request->input('id')
        );
        $album->delete();
        return response_success();
    }
}
