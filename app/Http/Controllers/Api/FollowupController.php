<?php

namespace App\Http\Controllers\Api;

use Carbon\Carbon;
use App\Models\Followup;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Builder;
use App\Http\Requests\Api\FollowupInfoRequest;
use App\Http\Requests\Api\FollowupCreateRequest;
use App\Http\Requests\Api\FollowupExecuteRequest;

class FollowupController extends Controller
{
    /**
     * 回访列表
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $rows  = $request->input('rows', 10);
        $sort  = $request->input('sort', 'created_at');
        $order = $request->input('order', 'desc');
        $query = Followup::query()
            ->select([
                'customer.name',
                'customer.sex',
                'followup.id',
                'followup.date',
                'followup.type',
                'followup.title',
                'followup.status',
                'followup.customer_id',
                'followup.followup_user',
                'followup.created_at',
                'followup.updated_at',
            ])
            ->with([
                'followupType:id,name',
                'followupUserInfo:id,name'
            ])
            ->when($request->input('status'), function (Builder $builder) use ($request) {
                $builder->where('followup.status', $request->input('status'));
            })
            ->when($request->input('date_start') && $request->input('date_end'), function (Builder $builder) use ($request) {
                $builder->whereBetween('followup.date', [
                    $request->input('date_start'),
                    $request->input('date_end'),
                ]);
            })
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
     * @param FollowupInfoRequest $request
     * @return JsonResponse
     */
    public function info(FollowupInfoRequest $request): JsonResponse
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
     * @param FollowupExecuteRequest $request
     * @return JsonResponse
     */
    public function execute(FollowupExecuteRequest $request): JsonResponse
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
     * @param FollowupCreateRequest $request
     * @return JsonResponse
     */
    public function create(FollowupCreateRequest $request): JsonResponse
    {
        $followup = Followup::query()->create(
            $request->formData()
        );

        // 更新顾客最近回访时间
        if ($followup->status == '2') {
            $followup->customer->update([
                'last_followup' => Carbon::now()->toDateTimeString()
            ]);
        }

        return response_success($followup);
    }
}
