<?php

namespace App\Http\Controllers\Web;

use App\Models\Menu;
use App\Models\Followup;
use App\Models\Reception;
use App\Models\Appointment;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\WorkbenchRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Database\Eloquent\Builder;

class WorkbenchController extends Controller
{
    /**
     * 流水牌
     * @param WorkbenchRequest $request
     * @return JsonResponse
     */
    public function dashboard(WorkbenchRequest $request): JsonResponse
    {
        $user = user();
        $menu = Menu::query()
            ->where('parentid', 1)
            ->where('type', 'web')
            ->where('menu_type', 'menu')
            ->when(!$user->isSuperUser(), fn(Builder $query) => $query->whereIn('permission', array_keys(array_filter($user->permissions ?? []))))
            ->orderBy('order')
            ->orderBy('id')
            ->get();

        // 获取对应业务数据
        $menu->each(function ($item) use ($request) {
            $item->count = $request->getDashboardCount($item->permission);
        });

        return response_success($menu);
    }

    /**
     * 今日就诊
     * @param Request $request
     * @return JsonResponse
     */
    public function today(Request $request): JsonResponse
    {
        $rows  = $request->input('rows', 10);
        $sort  = $request->input('sort', 'appointments.created_at');
        $order = $request->input('order', 'desc');
        $query = Appointment::query()
            ->with([
                'customer:id,name,idcard,sex,age',
                'consultant:id,name',
                'doctor:id,name',
                'reception',
                'technician:id,name',
                'department:id,name',
            ])
            ->when($request->input('arrival') && $request->input('arrival') === 'true', fn($query) => $query->whereNotNull('arrival_time'))
            ->when($request->input('arrival') && $request->input('arrival') === 'false', fn($query) => $query->whereNull('arrival_time'))
            ->when($request->input('status'), fn($query) => $query->where('status', $request->input('status')))
            ->where('date', $request->input('date', date('Y-m-d')))
            ->orderBy($sort, $order)
            ->paginate($rows);

        return response_success([
            'rows'  => $query->items(),
            'total' => $query->total()
        ]);
    }

    /**
     * 回访提醒
     * @param Request $request
     * @return JsonResponse
     */
    public function followup(Request $request): JsonResponse
    {
        $rows  = $request->input('rows', 10);
        $query = Followup::query()
            ->with([
                'customer:id,idcard,name',
                'user:id,name',
                'followupType',
                'followupTool',
                'executeUserInfo:id,name',
                'followupUserInfo:id,name',
            ])
            ->orderBy('followup.created_at', 'desc')
            ->paginate($rows);

        return response_success([
            'rows'  => $query->items(),
            'total' => $query->total()
        ]);
    }

    /**
     * 分诊接待
     * @param WorkbenchRequest $request
     * @return JsonResponse
     */
    public function reception(WorkbenchRequest $request): JsonResponse
    {
        $rows    = $request->input('rows', 10);
        $sort    = $request->input('sort', 'created_at');
        $order   = $request->input('order', 'desc');
        $keyword = $request->input('keyword');

        $builder = Reception::query()
            ->with([
                'customer:id,name,idcard',
                'department:id,name',
                'receptionType:id,name',
                'receptionItems',
                'medium:id,name',
                'consultantUser:id,name',
                'ekUserRelation:id,name',
                'receptionUser:id,name',
                'doctorUser:id,name',
                'user:id,name',
            ])
            ->select([
                'reception.*'
            ])
            ->leftJoin('customer', 'reception.customer_id', '=', 'customer.id')
            ->whereBetween('reception.created_at', [
                Carbon::parse($request->input('created_at.0'))->startOfDay(),
                Carbon::parse($request->input('created_at.1'))->endOfDay()
            ])
            ->queryConditions('WorkbenchReception')
            ->when($keyword, fn(Builder $query) => $query->whereLike('customer.keyword', '%' . $keyword . '%'))
            // 权限限制
            ->when(!user()->hasAnyAccess(['superuser', 'reception.view.all']), function (Builder $query) {
                $ids = user()->getReceptionViewUsersPermission();
                $query->where(function ($query) use ($ids) {
                    $query->whereIn('reception.user_id', $ids)->orWhere('reception.reception', $ids);
                });
            })
            ->orderBy('reception.' . $sort, $order);

        // 执行分页
        $query = $builder->paginate($rows);
        $query->append(['status_text']);

        return response_success([
            'rows'      => $query->items(),
            'total'     => $query->total(),
            'dashboard' => $request->getReceptionDashboard($builder)
        ]);
    }

    /**
     * 到店操作
     * @param WorkbenchRequest $request
     * @return JsonResponse
     */
    public function arrival(WorkbenchRequest $request): JsonResponse
    {
        $appointment = Appointment::query()->find(
            $request->input('id')
        );
        $appointment->update([
            'status'       => 2,
            'arrival_time' => now(),
        ]);
        return response_success($appointment);
    }
}
