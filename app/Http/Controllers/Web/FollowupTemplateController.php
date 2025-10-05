<?php

namespace App\Http\Controllers\Web;

use Exception;
use Throwable;
use Illuminate\Http\Request;
use App\Exceptions\HisException;
use App\Models\FollowupTemplate;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Models\FollowupTemplateType;
use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Builder;
use App\Http\Requests\Web\FollowupTemplateRequest;

class FollowupTemplateController extends Controller
{
    /**
     * 回访类别
     * @param Request $request
     * @return JsonResponse
     */
    public function type(Request $request): JsonResponse
    {
        $type = FollowupTemplateType::query()
            ->select([
                'id',
                'name',
                'parentid',
                'child'
            ])
            ->get()
            ->toArray();
        return response_success(list_to_tree($type));
    }

    /**
     * 创建类别
     * @param FollowupTemplateRequest $request
     * @return JsonResponse
     */
    public function createType(FollowupTemplateRequest $request): JsonResponse
    {
        $type = FollowupTemplateType::query()->create(
            $request->typeFormData()
        );
        return response_success($type);
    }

    /**
     * 更新类别名称
     * @param FollowupTemplateRequest $request
     * @return JsonResponse
     */
    public function updateType(FollowupTemplateRequest $request): JsonResponse
    {
        $data = FollowupTemplateType::query()->find(
            $request->input('id')
        );
        $data->update(
            $request->typeFormData()
        );
        return response_success($data);
    }

    /**
     * 删除模板分类
     * @param FollowupTemplateRequest $request
     * @return JsonResponse
     * @throws HisException|Throwable
     */
    public function removeType(FollowupTemplateRequest $request): JsonResponse
    {
        DB::beginTransaction();
        try {

            // 先删除分类下,所有模板
            FollowupTemplate::query()
                ->whereIn('type_id', FollowupTemplateType::query()->find($request->input('id'))->getAllChild()->pluck('id'))
                ->delete();

            // 再删除分类表
            FollowupTemplateType::query()
                ->find($request->input('id'))
                ->delete();

            DB::commit();
            return response_success();

        } catch (Exception $e) {
            DB::rollBack();
            throw new HisException($e->getMessage());
        }
    }

    /**
     * 回访模板列表
     * @param FollowupTemplateRequest $request
     * @return JsonResponse
     */
    public function index(FollowupTemplateRequest $request): JsonResponse
    {
        $rows    = $request->input('rows', 10);
        $sort    = $request->input('sort', 'id');
        $order   = $request->input('order', 'desc');
        $type_id = $request->input('type_id');
        $keyword = $request->input('keyword');
        $query   = FollowupTemplate::query()
            ->with([
                'type:id,name',
                'user:id,name',
                'details',
                'details.user:id,name',
                'details.followupType:id,name',
                'details.role:id,name,value'
            ])
            ->whereIn('type_id', FollowupTemplateType::query()->find($type_id)->getAllChild()->pluck('id'))
            ->when($keyword, fn(Builder $query) => $query->where('title', 'like', '%' . $keyword . '%'))
            ->orderBy($sort, $order)
            ->paginate($rows);

        return response_success([
            'rows'  => $query->items(),
            'total' => $query->total()
        ]);
    }

    /**
     * 创建回访计划
     * @param FollowupTemplateRequest $request
     * @return JsonResponse
     */
    public function create(FollowupTemplateRequest $request): JsonResponse
    {
        $template = FollowupTemplate::query()->create(
            $request->formData()
        );
        $template->details()->createMany(
            $request->detailsData()
        );
        return response_success($template);
    }

    /**
     * 更新模板
     * @param FollowupTemplateRequest $request
     * @return JsonResponse
     */
    public function update(FollowupTemplateRequest $request): JsonResponse
    {
        $template = FollowupTemplate::query()->find(
            $request->input('id')
        );
        $template->update(
            $request->formData()
        );
        $template->details()->delete();
        $template->details()->createMany(
            $request->detailsData()
        );
        return response_success($template);
    }

    /**
     * 删除模板
     * @param FollowupTemplateRequest $request
     * @return JsonResponse
     */
    public function remove(FollowupTemplateRequest $request): JsonResponse
    {
        FollowupTemplate::query()->find($request->input('id'))->delete();
        return response_success();
    }
}
