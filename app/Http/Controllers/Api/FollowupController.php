<?php

namespace App\Http\Controllers\Api;

use Carbon\Carbon;
use App\Models\Followup;
use App\Enums\FollowupStatus;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Builder;
use App\Http\Requests\Api\FollowupRequest;

class FollowupController extends Controller
{
    /**
     * 回访列表
     * @param FollowupRequest $request
     * @return JsonResponse
     */
    public function index(FollowupRequest $request): JsonResponse
    {
        $rows    = $request->input('rows', 10);
        $sort    = $request->input('sort', 'created_at');
        $order   = $request->input('order', 'desc');
        $type    = $request->input('type');
        $status  = $request->input('status');
        $keyword = $request->input('keyword');

        $query = Followup::query()
            ->select([
                'followup.*',
            ])
            ->with([
                'customer:id,sex,name,idcard',
                'followupType:id,name',
                'followupTool:id,name',
                'followupUserInfo:id,name',
            ])
            ->whereBetween('followup.date', [
                $request->input('date_start'),
                $request->input('date_end'),
            ])
            ->when($type, fn(Builder $query) => $query->whereIn('followup.type', $type))
            ->when($status, fn(Builder $query) => $query->where('followup.status', $status))
            ->when($keyword, fn(Builder $query) => $query->whereLike('customer.keyword', '%' . $keyword . '%'))
            ->leftJoin('customer', 'customer.id', '=', 'followup.customer_id')
            ->orderBy($sort, $order)
            ->paginate($rows);

        return response_success([
            'rows'  => $query->items(),
            'total' => $query->total()
        ]);
    }

    /**
     * 回访信息
     * @param FollowupRequest $request
     * @return JsonResponse
     */
    public function info(FollowupRequest $request): JsonResponse
    {
        $followup = Followup::query()->find(
            $request->input('id')
        );
        $followup->load([
            'customer:id,name,idcard',
            'followupUserInfo:id,name',
            'executeUserInfo:id,name',
            'user:id,name',
            'followupType:id,name',
            'followupTool:id,name'
        ]);
        return response_success($followup);
    }

    /**
     * 执行回访
     * @param FollowupRequest $request
     * @return JsonResponse
     */
    public function execute(FollowupRequest $request): JsonResponse
    {
        $followup = Followup::query()->find(
            $request->input('id')
        );

        // 更新
        $followup->update(
            $request->formData()
        );

        // 写入顾客最近回访时间
        $followup->customer->update([
            'last_followup' => Carbon::now()->toDateTimeString()
        ]);
        return response_success($followup);
    }

    /**
     * 创建回访或者回访计划
     * @param FollowupRequest $request
     * @return JsonResponse
     */
    public function create(FollowupRequest $request): JsonResponse
    {
        $followup = Followup::query()->create(
            $request->formData()
        );

        // 更新顾客最近回访时间
        if ($followup->status == FollowupStatus::COMPLETED) {
            $followup->customer->update([
                'last_followup' => Carbon::now()->toDateTimeString()
            ]);
        }

        return response_success($followup);
    }
}
