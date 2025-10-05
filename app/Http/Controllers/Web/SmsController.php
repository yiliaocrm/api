<?php

namespace App\Http\Controllers\Web;

use App\Models\Sms;
use App\Jobs\SendSmsJob;
use App\Models\SmsCategory;
use Illuminate\Support\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\SmsRequest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class SmsController extends Controller
{
    /**
     * 获取短信模板分类及模板数据（用于el-tree组件）
     * @return JsonResponse
     */
    public function categories(): JsonResponse
    {
        $categories = SmsCategory::query()
            ->with([
                'templates' => fn($query) => $query->where('disabled', false)
            ])
            ->orderBy('sort')
            ->get();

        $treeData = $categories->map(function ($category) {
            return [
                'id'       => $category->id,
                'uuid'     => Str::uuid(),
                'label'    => $category->name,
                'type'     => 'category',
                'children' => $category->templates->map(function ($template) {
                    return [
                        'id'          => $template->id,
                        'uuid'        => Str::uuid(),
                        'label'       => $template->name,
                        'type'        => 'template',
                        'content'     => $template->content,
                        'category_id' => $template->category_id,
                    ];
                })->toArray()
            ];
        });

        return response_success($treeData);
    }

    /**
     * 短信发送记录列表
     * @param SmsRequest $request
     * @return JsonResponse
     */
    public function index(SmsRequest $request): JsonResponse
    {
        $rows  = $request->input('rows', 10);
        $sort  = $request->input('sort', 'id');
        $order = $request->input('order', 'desc');
        $phone = $request->input('phone');

        $query = Sms::query()
            ->with([
                'user:id,name'
            ])
            ->when($phone, fn(Builder $query) => $query->where('phone', 'like', "%{$phone}%"))
            ->whereBetween('sms.created_at', [
                Carbon::parse($request->input('created_at.0'))->startOfDay(),
                Carbon::parse($request->input('created_at.1'))->endOfDay()
            ])
            ->orderBy($sort, $order)
            ->paginate($rows);

        $query->append(['status_text']);

        return response_success([
            'rows'  => $query->items(),
            'total' => $query->total(),
        ]);
    }

    /**
     * 短信发送概况
     * @param SmsRequest $request
     * @return JsonResponse
     */
    public function dashboard(SmsRequest $request): JsonResponse
    {
        // 统计每日数据
        $rows = Sms::query()
            ->selectRaw('date(created_at) AS date')
            ->selectRaw('count(1) AS total')
            ->selectRaw("count(if(status = 'sent', 1, null)) AS sent")
            ->selectRaw("count(if(status = 'failed', 1, null)) AS failed")
            ->when($request->input('start') && $request->input('end'), function ($query) use ($request) {
                $query->whereBetween('created_at', [
                    Carbon::parse($request->input('start')),
                    Carbon::parse($request->input('end'))->endOfDay(),
                ]);
            })
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date', 'asc')
            ->get();

        // 仪表盘数据
        $dashboard = Sms::query()
            ->selectRaw('count(1) AS total')
            ->selectRaw("count(if(status = 'sent', 1, null)) AS sent")
            ->selectRaw("count(if(status = 'failed', 1, null)) AS failed")
            ->when($request->input('start') && $request->input('end'), function ($query) use ($request) {
                $query->whereBetween('created_at', [
                    Carbon::parse($request->input('start')),
                    Carbon::parse($request->input('end'))->endOfDay(),
                ]);
            })
            ->first();

        return response_success([
            'rows'      => $request->formatterRows($rows),
            'dashboard' => $dashboard
        ]);
    }

    /**
     * 发送短信
     * @param SmsRequest $request
     * @return JsonResponse
     */
    public function send(SmsRequest $request): JsonResponse
    {
        $sms = Sms::query()->create(
            $request->formData()
        );
        SendSmsJob::dispatch($sms);
        return response_success(msg: '短信发送任务已添加');
    }
}
