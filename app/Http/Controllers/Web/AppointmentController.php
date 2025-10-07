<?php

namespace App\Http\Controllers\Web;

use App\Models\Appointment;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Exports\AppointmentExport;
use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Builder;
use App\Http\Requests\Web\AppointmentRequest;

class AppointmentController extends Controller
{
    /**
     * 预约列表
     * @param AppointmentRequest $request
     * @return JsonResponse
     */
    public function index(AppointmentRequest $request): JsonResponse
    {
        $sort    = $request->input('sort', 'created_at');
        $order   = $request->input('order', 'desc');
        $rows    = $request->input('rows', 10);
        $status  = $request->input('status');
        $keyword = $request->input('keyword');
        $query   = Appointment::query()
            ->with([
                'doctor:id,name',
                'consultant:id,name',
                'technician:id,name',
                'customer:id,name,idcard',
                'department:id,name',
                'createUser:id,name'
            ])
            ->select([
                'appointments.*'
            ])
            ->leftJoin('customer', 'customer.id', '=', 'appointments.customer_id')
            ->queryConditions('AppointmentIndex')
            ->when($keyword, fn(Builder $query) => $query->where('customer.keyword', 'like', "%{$keyword}%"))
            ->when($request->has('status'), fn(Builder $query) => $query->where('status', $status))
            ->orderBy("appointments.{$sort}", $order)
            ->paginate($rows);

        $query->append(['status_text']);

        return response_success([
            'rows'  => $query->items(),
            'total' => $query->total()
        ]);
    }

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
                'customer:id,name,idcard,file_number',
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
     * 导出[预约记录]
     * @param Request $request
     * @return AppointmentExport
     */
    public function export(Request $request): AppointmentExport
    {
        return new AppointmentExport($request);
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
