<?php

namespace App\Http\Controllers\Web;

use App\Models\PrintTemplate;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\PrintTemplateRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;


class PrintTemplateController extends Controller
{
    /**
     * 模板列表
     * @param Request $request
     * @return JsonResponse
     */
    public function manage(Request $request): JsonResponse
    {
        $rows  = $request->input('rows', 10);
        $query = PrintTemplate::query()
            ->when($request->filled('name'), fn($q) => $q->where('name', 'like', "%{$request->input('name')}%"))
            ->when($request->input('type'), fn($q) => $q->whereIn('type', $request->input('type')))
            ->orderBy('id', 'desc')
            ->paginate($rows);

        return response_success([
            'rows'  => $query->items(),
            'total' => $query->total()
        ]);
    }

    /**
     * 加载模板信息
     * @param PrintTemplateRequest $request
     * @return JsonResponse
     */
    public function info(PrintTemplateRequest $request): JsonResponse
    {
        $template = PrintTemplate::query()->find(
            $request->input('id')
        );
        return response_success($template);
    }

    /**
     * 设置默认模板
     * @param PrintTemplateRequest $request
     * @return JsonResponse
     */
    public function default(PrintTemplateRequest $request): JsonResponse
    {
        $default  = $request->input('default', true);
        $template = PrintTemplate::query()->find(
            $request->input('id')
        );

        // 设置默认模板之前关闭其他默认模板
        if ($default) {
            PrintTemplate::query()->where('type', $template->type)->update(['default' => 0]);
        }

        $template->update(['default' => $default]);
        return response_success($template);
    }

    /**
     * 创建模板
     * @param PrintTemplateRequest $request
     * @return JsonResponse
     */
    public function create(PrintTemplateRequest $request): JsonResponse
    {
        $template = PrintTemplate::query()->create(
            $request->fillData()
        );
        return response_success($template);
    }

    /**
     * 复制模板
     * @param PrintTemplateRequest $request
     * @return JsonResponse
     */
    public function copy(PrintTemplateRequest $request): JsonResponse
    {
        $template = PrintTemplate::query()->find(
            $request->input('id')
        );

        // 拷贝
        $new          = $template->replicate();
        $new->name    = $request->input('name');
        $new->default = 0;
        $new->system  = 0;
        $new->save();

        return response_success($new);
    }

    /**
     * 更新
     * @param PrintTemplateRequest $request
     * @return JsonResponse
     */
    public function update(PrintTemplateRequest $request): JsonResponse
    {
        $print = PrintTemplate::query()->find(
            $request->input('id')
        );
        $print->update(
            $request->fillData()
        );
        return response_success($print);
    }

    /**
     * 删除模板
     * @param PrintTemplateRequest $request
     * @return JsonResponse
     */
    public function remove(PrintTemplateRequest $request): JsonResponse
    {
        PrintTemplate::query()->find($request->input('id'))->delete();
        return response_success();
    }
}
