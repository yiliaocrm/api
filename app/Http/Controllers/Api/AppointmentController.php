<?php

namespace App\Http\Controllers\Api;

use App\Models\Appointment;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\AppointmentRequest;
use Illuminate\Http\JsonResponse;

class AppointmentController extends Controller
{
    /**
     * 读取预约看板配置
     * @param AppointmentRequest $request
     * @return JsonResponse
     */
    public function config(AppointmentRequest $request): JsonResponse
    {
        return response_success(
            $request->getConfig()
        );
    }

    /**
     * 列表视图
     * @param AppointmentRequest $request
     * @return JsonResponse
     */
    public function index(AppointmentRequest $request): JsonResponse
    {
        $sort  = $request->input('sort', 'start');
        $order = $request->input('order', 'asc');
        $rows  = $request->input('rows', 10);
        $date  = $request->input('date');
        $query = Appointment::query()
            ->with([
                'customer:id,idcard,name,sex'
            ])
            ->where('date', $date)
            ->orderBy($sort, $order)
            ->paginate($rows);

        return response_success([
            'rows'  => $query->items(),
            'total' => $query->total()
        ]);
    }

    /**
     * 看板视图
     * @param AppointmentRequest $request
     * @return JsonResponse
     */
    public function dashboard(AppointmentRequest $request): JsonResponse
    {
        return response_success([
            'events'    => $request->structEvents(),
            'resources' => $request->structResources()
        ]);
    }

    /**
     * 创建预约
     * @param AppointmentRequest $request
     * @return JsonResponse
     */
    public function create(AppointmentRequest $request): JsonResponse
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
