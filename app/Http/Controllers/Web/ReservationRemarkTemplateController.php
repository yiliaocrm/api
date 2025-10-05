<?php

namespace App\Http\Controllers\Web;

use Exception;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\ReservationRemarkTemplateRequest;
use App\Models\ReservationRemarkTemplate;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Database\Eloquent\Builder;

class ReservationRemarkTemplateController extends Controller
{
    /**
     * 网电咨询备注模板列表
     * @param Request $request
     * @return JsonResponse
     */
    public function manage(Request $request): JsonResponse
    {
        $rows  = $request->input('rows', 10);
        $sort  = $request->input('sort', 'created_at');
        $order = $request->input('order', 'desc');
        $query = ReservationRemarkTemplate::query()
            ->with('createUser')
            ->when($title = $request->input('title'), fn(Builder $query) => $query->whereLike('title', "%{$title}%"))
            ->orderBy($sort, $order)
            ->paginate($rows);

        return response_success([
            'rows'  => $query->items(),
            'total' => $query->total(),
        ]);
    }

    /**
     * 创建模板
     * @param ReservationRemarkTemplateRequest $request
     * @return JsonResponse
     */
    public function create(ReservationRemarkTemplateRequest $request): JsonResponse
    {
        $template = ReservationRemarkTemplate::query()->create(
            $request->formData()
        );
        return response_success($template);
    }

    /**
     * 更新咨询备注模板
     * @param ReservationRemarkTemplateRequest $request
     * @return JsonResponse
     */
    public function update(ReservationRemarkTemplateRequest $request): JsonResponse
    {
        $template = ReservationRemarkTemplate::query()->find(
            $request->input('id')
        );
        $template->update(
            $request->formData()
        );
        return response_success($template);
    }

    /**
     * 删除模板
     * @param ReservationRemarkTemplateRequest $request
     * @return JsonResponse
     * @throws Exception
     */
    public function remove(ReservationRemarkTemplateRequest $request): JsonResponse
    {
        ReservationRemarkTemplate::query()->find($request->input('id'))->delete();
        return response_success();
    }
}
