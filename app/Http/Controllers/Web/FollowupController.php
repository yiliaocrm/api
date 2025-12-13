<?php

namespace App\Http\Controllers\Web;

use App\Models\Followup;
use Illuminate\Support\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\FollowupRequest;

class FollowupController extends Controller
{
    /**
     * 回访详情
     * @param FollowupRequest $request
     * @return JsonResponse
     */
    public function info(FollowupRequest $request): JsonResponse
    {
        $followup = Followup::query()->find(
            $request->input('id')
        );
        $followup->load([
            'customer:id,idcard,name',
            'customer.phones',
            'customer.phones.relationship:id,name'
        ]);
        return response_success($followup);
    }

    /**
     * 创建回访
     * @param FollowupRequest $request
     * @return JsonResponse
     */
    public function create(FollowupRequest $request)
    {
        // 创建回访记录
        $followup = Followup::query()->create(
            $request->formData()
        );

        // 更新顾客最近回访时间
        if ($followup->status == 2) {
            $followup->customer->update([
                'last_followup' => Carbon::now()->toDateTimeString()
            ]);
        }

        return response_success($followup);
    }

    /**
     * 更新回访
     * @param FollowupRequest $request
     * @return JsonResponse
     */
    public function update(FollowupRequest $request)
    {
        // 更新回访记录
        $followup = Followup::query()->find(
            $request->input('id')
        );
        $followup->update(
            $request->formData()
        );
        // 更新顾客最近回访时间
        if ($followup->status == 2) {
            $followup->customer->update([
                'last_followup' => Carbon::now()->toDateTimeString()
            ]);
        }
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

        // 执行回访记录
        $followup->update(
            $request->executeData()
        );

        // 写入顾客最近回访时间
        $followup->customer->update([
            'last_followup' => Carbon::now()->toDateTimeString()
        ]);

        $followup->load([
            'customer:id,idcard,name',
            'user:id,name',
            'followupType',
            'followupTool',
            'executeUserInfo:id,name',
            'followupUserInfo:id,name',
        ]);

        $followup->append(['status_text']);

        return response_success($followup);
    }

    /**
     * 删除回访
     * @param FollowupRequest $request
     * @return JsonResponse
     */
    public function remove(FollowupRequest $request): JsonResponse
    {
        Followup::query()->find($request->input('id'))->delete();
        return response_success();
    }

    /**
     * 批量插入回访模板
     * @param FollowupRequest $request
     * @return JsonResponse
     */
    public function batchInsert(FollowupRequest $request): JsonResponse
    {
        DB::table('followup')->insert(
            $request->batchInsertData()
        );
        return response_success();
    }

    /**
     * 发起呼叫
     * @param FollowupRequest $request
     * @return JsonResponse
     */
    public function originate(FollowupRequest $request): JsonResponse
    {
        return response_success();
    }
}
