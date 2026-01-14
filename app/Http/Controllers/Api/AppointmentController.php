<?php

namespace App\Http\Controllers\Api;

use App\Models\Appointment;
use App\Http\Controllers\Controller;
use App\Services\AppointmentService;
use App\Http\Requests\Api\AppointmentRequest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;

class AppointmentController extends Controller
{
    public function __construct(
        protected AppointmentService $appointmentService
    )
    {
    }

    /**
     * 读取预约看板配置
     * @return JsonResponse
     */
    public function config(): JsonResponse
    {
        return response_success(
            $this->appointmentService->getConfig()
        );
    }

    /**
     * 列表视图
     * @param AppointmentRequest $request
     * @return JsonResponse
     */
    public function index(AppointmentRequest $request): JsonResponse
    {
        $sort        = $request->input('sort', 'start');
        $order       = $request->input('order', 'asc');
        $rows        = $request->input('rows', 10);
        $date        = $request->input('date');
        $view        = $request->input('view');
        $status      = $request->input('status');
        $resource_id = $request->input('resource_id');

        $query = Appointment::query()
            ->with([
                'customer:id,idcard,name,sex'
            ])
            ->where('date', $date)
            ->whereIn('status', $status)
            ->when($view && $resource_id, function (Builder $query) use ($view, $resource_id) {
                $query->whereIn($view . '_id', $resource_id);
            })
            ->orderBy($sort, $order)
            ->paginate($rows);

        $query->append(['status_text', 'type_text']);

        return response_success([
            'rows'   => $query->items(),
            'total'  => $query->total(),
            'status' => $request->structStatus()
        ]);
    }

    /**
     * 看板视图
     * @param AppointmentRequest $request
     * @return JsonResponse
     */
    public function dashboard(AppointmentRequest $request): JsonResponse
    {
        $date        = $request->input('date');
        $view        = $request->input('view');
        $resourceIds = $request->input('resource_id', []);

        return response_success([
            'events'    => $request->structEvents(),
            'status'    => $request->structStatus(),
            'resources' => $this->appointmentService->getResourcesData($view, $resourceIds, $date, $date)
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
