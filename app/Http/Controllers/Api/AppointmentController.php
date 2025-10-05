<?php

namespace App\Http\Controllers\Api;

use App\Models\Role;
use App\Models\Room;
use App\Models\Department;
use App\Models\Appointment;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Builder;
use App\Http\Requests\Api\AppointmentCreateRequest;
use App\Http\Requests\Api\AppointmentDashboardRequest;

class AppointmentController extends Controller
{
    /**
     * 读取预约看板配置
     * @return JsonResponse
     */
    public function config(): JsonResponse
    {
        // 当前登录门店信息
        $store          = store();
        $room           = Room::query()->orderByDesc('appointment_order')->orderByDesc('id')->get();
        $department     = Department::query()->where('primary', 1)->orderByDesc('appointment_order')->orderByDesc('id')->get();
        $doctorRole     = Role::query()->where('slug', 'doctor')->first();
        $technicianRole = Role::query()->where('slug', 'technician')->first();
        $consultantRole = Role::query()->where('slug', 'consultant')->first();

        return response_success([
            'room'           => $room ?? [],
            'doctor'         => $doctorRole?->users ? $doctorRole->users()->where('banned', 0)->orderByDesc('appointment_order')->orderByDesc('id')->get() : [],
            'technician'     => $technicianRole?->users ? $technicianRole->users()->where('banned', 0)->orderByDesc('appointment_order')->orderByDesc('id')->get() : [],
            'consultant'     => $consultantRole?->users ? $consultantRole->users()->where('banned', 0)->orderByDesc('appointment_order')->orderByDesc('id')->get() : [],
            'department'     => $department ?? [],
            'business_start' => $store->business_start,
            'business_end'   => $store->business_end,
            'slot_duration'  => $store->slot_duration,
        ]);
    }

    /**
     * 列表视图
     * @param Request $request
     * @return JsonResponse
     */
    public function lists(Request $request): JsonResponse
    {
        $sort  = $request->input('sort', 'start');
        $order = $request->input('order', 'asc');
        $rows  = $request->input('rows', 10);
        $query = Appointment::query()
            ->select([
                'id', 'customer_id', 'date', 'start', 'end', 'remark'
            ])
            ->with([
                'customer:id,idcard,name,sex'
            ])
            ->when($request->input('date'), fn(Builder $query) => $query->where('date', $request->input('date')))
            ->orderBy($sort, $order)
            ->paginate($rows);

        return response_success([
            'rows'  => $query->items(),
            'total' => $query->total()
        ]);
    }

    /**
     * 看板视图
     * @param AppointmentDashboardRequest $request
     * @return JsonResponse
     */
    public function dashboard(AppointmentDashboardRequest $request): JsonResponse
    {
        return response_success([
            'events'    => $request->structEvents(),
            'resources' => $request->structResources()
        ]);
    }

    /**
     * 创建预约
     * @param AppointmentCreateRequest $request
     * @return JsonResponse
     */
    public function create(AppointmentCreateRequest $request): JsonResponse
    {
        $appointment = Appointment::query()->create(
            $request->formData()
        );
        // 加载关联顾客信息
        $appointment->load([
            'doctor:id,name',
            'customer:id,name,idcard',
            'consultant:id,name',
            'technician:id,name',
            'department:id,name'
        ]);
        return response_success($appointment);
    }
}
