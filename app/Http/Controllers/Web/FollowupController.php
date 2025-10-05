<?php

namespace App\Http\Controllers\Web;

use App\Models\Followup;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Builder;
use App\Http\Requests\Web\FollowupRequest;

class FollowupController extends Controller
{
    /**
     * 回访列表
     * @param Request $request
     * @return JsonResponse
     */
    public function manage(Request $request)
    {
        $rows  = $request->input('rows', 10);
        $query = Followup::query()
            ->with([
                'customer:id,idcard,name',
            ])
            ->select('followup.*')
            // (提醒)回访日期
            ->when($request->input('date_start') && $request->input('date_end'), function (Builder $query) use ($request) {
                $query->whereBetween('followup.date', [
                    Carbon::parse($request->input('date_start')),
                    Carbon::parse($request->input('date_end'))->endOfDay()
                ]);
            })
            // 登记日期
            ->when($request->input('created_at_start') && $request->input('created_at_end'), function (Builder $query) use ($request) {
                $query->whereBetween('followup.created_at', [
                    Carbon::parse($request->input('created_at_start')),
                    Carbon::parse($request->input('created_at_end'))->endOfDay()
                ]);
            })
            // 执行时间
            ->when($request->input('time_start') && $request->input('time_end'), function (Builder $query) use ($request) {
                $query->whereBetween('followup.time', [
                    Carbon::parse($request->input('time_start')),
                    Carbon::parse($request->input('time_end'))->endOfDay()
                ]);
            })
            // 回访类型
            ->when($request->input('type'), function (Builder $query) use ($request) {
                $query->where('followup.type', $request->input('type'));
            })
            ->when($request->input('tool'), function (Builder $query) use ($request) {
                $query->where('followup.tool', $request->input('tool'));
            })
            // 回访状态
            ->when($request->input('status'), function (Builder $query) use ($request) {
                $query->where('followup.status', $request->input('status'));
            })
            // 回访提醒人员
            ->when($request->input('followup_user'), function (Builder $query) use ($request) {
                $query->where('followup.followup_user', $request->input('followup_user'));
            })
            // 回访提醒部门
            ->when($request->input('followup_user_department'), function (Builder $query) use ($request) {
                $query->leftJoin('users', 'users.id', '=', 'followup.followup_user')->where('users.department_id', $request->input('followup_user_department'));
            })
            // 回访执行人员
            ->when($request->input('execute_user'), function (Builder $query) use ($request) {
                $query->where('followup.execute_user', $request->input('execute_user'));
            })
            // 创建人员
            ->when($request->input('user_id'), function (Builder $query) use ($request) {
                $query->where('followup.user_id', $request->input('user_id'));
            })
            // 回访主题
            ->when($request->input('title'), function (Builder $query) use ($request) {
                $query->where('followup.title', 'like', '%' . $request->input('title') . '%');
            })
            // 回访备注
            ->when($request->input('remark'), function (Builder $query) use ($request) {
                $query->where('followup.remark', 'like', '%' . $request->input('remark') . '%');
            })
            ->when(!user()->hasAnyAccess(['superuser', 'followup.view.all']), function (Builder $query) {
                $query->where('followup.followup_user', user()->id);
            })
            ->orderBy('followup.created_at', 'desc')
            ->paginate($rows);

        return response_success([
            'rows'  => $query->items(),
            'total' => $query->total()
        ]);
    }

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
