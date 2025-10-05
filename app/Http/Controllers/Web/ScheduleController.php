<?php

namespace App\Http\Controllers\Web;

use Exception;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Schedule;
use App\Models\ScheduleRule;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Builder;
use App\Http\Requests\Web\ScheduleRequest;

class ScheduleController extends Controller
{
    /**
     * 排班管理
     * @param Request $request
     * @return JsonResponse
     */
    public function scheduling(Request $request): JsonResponse
    {
        // 参与排班人员
        $users = User::query()
            ->select([
                'id',
                'name AS title'
            ])
            ->where('scheduleable', 1)
            ->where('banned', 0)
            ->orderByDesc('id')
            ->get();

        // 排班数据
        $schedule = Schedule::query()
            ->where('store_id', store()->id)
            ->whereBetween('start', [
                Carbon::parse($request->input('start')),
                Carbon::parse($request->input('end'))->endOfDay()
            ])
            ->get();

        return response_success([
            'users'    => $users,
            'schedule' => $schedule
        ]);
    }

    /**
     * 创建排班
     * @param ScheduleRequest $request
     * @return JsonResponse
     */
    public function createScheduling(ScheduleRequest $request): JsonResponse
    {
        // 一天时间内只允许一个排班
        Schedule::query()
            ->whereBetween('start', [
                Carbon::parse($request->input('date_start')),
                Carbon::parse($request->input('date_end'))->endOfDay()
            ])
            ->where('user_id', $request->input('user_id'))
            ->where('store_id', store()->id)
            ->delete();

        // 重新排班
        Schedule::query()->insert(
            $request->formData()
        );

        return response_success();
    }

    /**
     * 撤销排班
     * @param ScheduleRequest $request
     * @return JsonResponse
     */
    public function clearScheduling(ScheduleRequest $request): JsonResponse
    {
        Schedule::query()
            ->whereBetween('start', [
                Carbon::parse($request->input('date_start')),
                Carbon::parse($request->input('date_end'))->endOfDay()
            ])
            ->where('user_id', $request->input('user_id'))
            ->where('store_id', store()->id)
            ->delete();

        return response_success();
    }

    /**
     * 班次规则
     * @param Request $request
     * @return JsonResponse
     */
    public function rule(Request $request): JsonResponse
    {
        $sort  = $request->input('sort', 'created_at');
        $order = $request->input('order', 'desc');
        $data  = ScheduleRule::query()
            ->where('store_id', store()->id)
            ->when($request->input('name'), function (Builder $builder) use ($request) {
                $builder->where('name', 'like', '%' . $request->input('name') . '%');
            })
            ->orderBy($sort, $order)
            ->get();
        return response_success($data);
    }

    /**
     * 创建班次
     * @param ScheduleRequest $request
     * @return JsonResponse
     */
    public function createRule(ScheduleRequest $request): JsonResponse
    {
        $rule = ScheduleRule::query()->create(
            $request->ruleFormData()
        );
        return response_success($rule);
    }

    /**
     * 删除班次规则
     * @param ScheduleRequest $request
     * @return JsonResponse
     * @throws Exception
     */
    public function removeRule(ScheduleRequest $request): JsonResponse
    {
        ScheduleRule::query()->find($request->input('id'))->delete();
        return response_success();
    }

    /**
     * 更新班次
     * @param ScheduleRequest $request
     * @return JsonResponse
     */
    public function updateRule(ScheduleRequest $request): JsonResponse
    {
        $rule = ScheduleRule::query()->find(
            $request->input('id')
        );
        $rule->update(
            $request->ruleFormData()
        );
        return response_success();
    }
}
