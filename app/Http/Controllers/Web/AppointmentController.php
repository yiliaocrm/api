<?php

namespace App\Http\Controllers\Web;

use App\Models\Appointment;
use App\Enums\AppointmentStatus;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Builder;
use App\Http\Requests\Web\AppointmentRequest;

class AppointmentController extends Controller
{
    /**
     * 预约设置
     * @param AppointmentRequest $request
     * @return JsonResponse
     */
    public function getConfig(AppointmentRequest $request): JsonResponse
    {
        return response_success(
            $request->getConfigData()
        );
    }

    /**
     * 更新预约设置
     * @param AppointmentRequest $request
     * @return JsonResponse
     */
    public function saveConfig(AppointmentRequest $request): JsonResponse
    {
        $request->saveConfig();
        return response_success();
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
        $appointment->load([
            'doctor:id,name',
            'customer:id,name,idcard',
            'consultant:id,name',
            'technician:id,name',
            'department:id,name'
        ]);
        return response_success($appointment);
    }

    /**
     * 删除预约
     * @param AppointmentRequest $request
     * @return JsonResponse
     */
    public function remove(AppointmentRequest $request): JsonResponse
    {
        Appointment::query()->find($request->input('id'))->delete();
        return response_success();
    }

    /**
     * 查看预约记录
     * @param AppointmentRequest $request
     * @return JsonResponse
     */
    public function info(AppointmentRequest $request): JsonResponse
    {
        $appointment = Appointment::query()->find(
            $request->input('id')
        );
        $appointment->load([
            'room:id,name',
            'doctor:id,name',
            'customer:id,idcard,sex,name,file_number',
            'consultant:id,name',
            'technician:id,name',
            'department:id,name'
        ]);
        return response_success($appointment);
    }

    /**
     * 更新预约
     * @param AppointmentRequest $request
     * @return JsonResponse
     */
    public function update(AppointmentRequest $request): JsonResponse
    {
        $appointment = Appointment::query()->find(
            $request->input('id')
        );
        $appointment->update(
            $request->formData()
        );
        $appointment->load([
            'room:id,name',
            'doctor:id,name',
            'customer:id,idcard,sex,name',
            'consultant:id,name',
            'technician:id,name',
            'department:id,name'
        ]);
        return response_success($appointment);
    }

    /**
     * 加载指定日期内的fullcalendar事件
     * @param AppointmentRequest $request
     * @return JsonResponse
     */
    public function events(AppointmentRequest $request): JsonResponse
    {
        return response_success([
            'resources' => $request->structResources(),
            'events'    => $request->structEvents(),
            'status'    => $request->structStatus()
        ]);
    }

    /**
     * 预约排期(新增、编辑预约右侧显示)
     * @param AppointmentRequest $request
     * @return JsonResponse
     */
    public function getSchedule(AppointmentRequest $request): JsonResponse
    {
        $appointment = Appointment::query()
            ->with([
                'doctor:id,name',
                'consultant:id,name',
                'technician:id,name',
                'customer:id,idcard,sex,name,file_number,birthday,ascription,consultant,remark',
                'department:id,name',
                'createUser:id,name'
            ])
            ->where('date', $request->input('date'))
            ->where($request->input('view') . '_id', $request->input('resource_id'))
            // 编辑不包含当前预约
            ->when($request->input('id'), fn(Builder $query) => $query->where('id', '!=', $request->input('id')))
            ->get();
        return response_success($appointment);
    }

    /**
     * 到店操作
     * @param AppointmentRequest $request
     * @return JsonResponse
     */
    public function arrival(AppointmentRequest $request): JsonResponse
    {
        $appointment = Appointment::query()->find(
            $request->input('id')
        );
        $appointment->update([
            'status'       => AppointmentStatus::ARRIVED,
            'arrival_time' => now(),
        ]);
        return response_success($appointment);
    }

    /**
     * 预约记录
     * @param AppointmentRequest $request
     * @return JsonResponse
     */
    public function history(AppointmentRequest $request): JsonResponse
    {
        $data = Appointment::query()
            ->with([
                'doctor:id,name'
            ])
            ->where('customer_id', $request->input('customer_id'))
            ->orderBy('created_at', 'desc')
            ->get();
        return response_success($data);
    }
}
