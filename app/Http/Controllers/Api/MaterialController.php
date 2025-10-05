<?php

namespace App\Http\Controllers\Api;

use App\Models\Material;
use App\Models\MaterialCategory;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Material\MaterialRequest;

class MaterialController extends Controller
{
    /**
     * 素材分类列表
     * @param MaterialRequest $request
     * @return JsonResponse
     */
    public function indexCategory(MaterialRequest $request): JsonResponse
    {
        $data = MaterialCategory::query()
            ->when($request->input('material_type'), function ($query, $value) {
                $query->where('material_type', $value);
            })
            ->orderBy('ranking')
            ->get();

        return response_success($data);
    }

    /**
     * 创建分类
     * @param MaterialRequest $request
     * @return JsonResponse
     */
    public function createCategory(MaterialRequest $request): JsonResponse
    {
        $category = MaterialCategory::query()->create(
            $request->toCategory()
        );
        return response_success($category);
    }

    /**
     * 更新分类
     * @param MaterialRequest $request
     * @return JsonResponse
     */
    public function updateCategory(MaterialRequest $request): JsonResponse
    {
        $category = $request->category();
        $category->update(
            $request->toCategory()
        );
        return response_success($category);
    }

    /**
     * 分类排序
     * @param MaterialRequest $request
     * @return JsonResponse
     */
    public function sortCategory(Materialrequest $request): JsonResponse
    {
        // 交换ranking值
        $category1 = MaterialCategory::query()->find($request->input('id1'));
        $category2 = MaterialCategory::query()->find($request->input('id2'));

        $ranking1 = $category1->ranking;
        $ranking2 = $category2->ranking;

        $category1->update([
            'ranking' => $ranking2,
        ]);

        $category2->update([
            'ranking' => $ranking1,
        ]);

        return response_success();
    }

    /**
     * 禁用分类
     * @param MaterialRequest $request
     * @return JsonResponse
     */
    public function disableCategory(Materialrequest $request): JsonResponse
    {
        $category = $request->category();

        $category->update([
            'is_enabled' => false,
        ]);

        return response_success([
            'material_category_id' => $category['id'],
        ]);
    }

    /**
     * 启用分类
     * @param MaterialRequest $request
     * @return JsonResponse
     */
    public function enableCategory(Materialrequest $request): JsonResponse
    {
        $category = $request->category();

        $category->update([
            'is_enabled' => true,
        ]);

        return response_success([
            'material_category_id' => $category['id'],
        ]);
    }

    /**
     * 删除分类
     * @param MaterialRequest $request
     * @return JsonResponse
     */
    public function removeCategory(Materialrequest $request): JsonResponse
    {
        $request->category()->delete();
        return response_success();
    }

    /**
     * 素材列表
     * @param MaterialRequest $request
     * @return JsonResponse
     */
    public function index(MaterialRequest $request): JsonResponse
    {
        $data = Material::query()
            ->with([
                'category:id,name',
                'creator:id,name',
                'statistics'
            ])
            ->when($request->input('keyword'), function ($query, $value) {
                $query->where('title', 'like', "%$value%")
                    ->orWhere('summary', 'like', "%$value%")
                    ->orWhere('content', 'like', "%$value%");
            })
            ->when($request->input('material_category_id'), function ($query, $value) {
                $query->where('material_category_id', $value);
            })
            ->when($request->input('type'), function ($query, $value) {
                $query->where('type', $value);
            })
            ->when($request->input('status'), function ($query, $value) {
                $status = match ($value) {
                    'active' => 1,
                    'deactive' => 0,
                };
                $query->where('status', $status);
            })
            ->latest('ranking')
            ->latest('id')
            ->paginate($request->input('rows', 20));

        return response_success([
            'rows'  => $data->items(),
            'total' => $data->total()
        ]);
    }

    /**
     * 创建素材
     * @param MaterialRequest $request
     * @param Attachment $service
     * @return JsonResponse
     */
    public function create(MaterialRequest $request, Attachment $service): JsonResponse
    {
        $data = collect($request->toMaterial())->only([
            'material_category_id',
            'type',
            'title',
            'summary',
            'content',
            'ranking',
            'is_share_disabled',
            'is_enabled_share_link',
            'is_enabled',
            'creator_id',
        ])->all();

        // 上传封面图
        if ($request->file('cover_image_file')?->isValid()) {
            $thumbnail  = $service->makeImageThumb($request->file('cover_image_file'), 'material_cover_images');
            $attachment = $service->upload($request->file('cover_image_file'), 'material_cover_images');

            $data['thumb']       = $thumbnail['file_path'];
            $data['cover_image'] = $attachment['file_path'];
        }

        // 上传视频
        if ($request->file('cover_video_file')?->isValid()) {
            $attachment = $service->upload($request->file('cover_video_file'), 'material_cover_videos');

            $data['cover_video'] = $attachment['file_path'];
        }

        $material = Material::query()->create($data);

        return response_success($material);
    }

    /**
     * 素材详情
     * @param MaterialRequest $request
     * @return JsonResponse
     */
    public function info(MaterialRequest $request): JsonResponse
    {
        $material = $request->material();

        return response_success($material);
    }

    /**
     * 更新素材
     * @param MaterialRequest $request
     * @return JsonResponse
     */
    public function update(MaterialRequest $request): JsonResponse
    {
        $material = $request->material()->update(
            $request->toMaterial()
        );

        return response_success($material);
    }

    /**
     * 删除素材
     * @param MaterialRequest $request
     * @return JsonResponse
     */
    public function remove(MaterialRequest $request): JsonResponse
    {
        $request->material()->delete();

        return response_success();
    }
}
