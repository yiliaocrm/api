<?php

namespace App\Http\Controllers\Web;

use Exception;
use App\Http\Controllers\Controller;
use App\Models\ConsultantRemarkTemplate;
use App\Http\Requests\Web\ConsultantRemarkTemplateRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Database\Eloquent\Builder;

class ConsultantRemarkTemplateController extends Controller
{
    /**
     * 列表
     * @param Request $request
     * @return JsonResponse
     */
    public function manage(Request $request): JsonResponse
    {
        $rows  = $request->input('rows', 10);
        $sort  = $request->input('sort', 'created_at');
        $order = $request->input('order', 'desc');
        $query = ConsultantRemarkTemplate::query()
            ->with([
                'createUser:id,name'
            ])
            ->when($request->input('title'), fn(Builder $query, $title) => $query->where('title', 'like', "%{$title}%"))
            ->orderBy($sort, $order)
            ->paginate($rows);

        return response_success([
            'rows'  => $query->items(),
            'total' => $query->total()
        ]);
    }

    /**
     * 创建模板
     * @param ConsultantRemarkTemplateRequest $request
     * @return JsonResponse
     */
    public function create(ConsultantRemarkTemplateRequest $request): JsonResponse
    {
        $template = ConsultantRemarkTemplate::query()->create(
            $request->formData()
        );
        return response_success($template);
    }

    /**
     * 更新咨询备注模板
     * @param ConsultantRemarkTemplateRequest $request
     * @return JsonResponse
     */
    public function update(ConsultantRemarkTemplateRequest $request): JsonResponse
    {
        $template = ConsultantRemarkTemplate::query()->find(
            $request->input('id')
        );
        $template->update(
            $request->formData()
        );
        return response_success($template);
    }

    /**
     * 删除模板
     * @param ConsultantRemarkTemplateRequest $request
     * @return JsonResponse
     * @throws Exception
     */
    public function remove(ConsultantRemarkTemplateRequest $request): JsonResponse
    {
        ConsultantRemarkTemplate::query()->find($request->input('id'))->delete();
        return response_success();
    }
}
