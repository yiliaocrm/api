<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\WorkbenchRequest;
use App\Models\Appointment;
use App\Models\Followup;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WorkbenchController extends Controller
{
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
