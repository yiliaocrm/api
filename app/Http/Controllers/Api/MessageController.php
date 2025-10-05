<?php

namespace App\Http\Controllers\Api;

use Carbon\Carbon;
use App\Models\Followup;
use App\Models\Customer;
use App\Models\Appointment;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Builder;

class MessageController extends Controller
{

    /**
     * 消息首页
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        // 今日未回访
        $followup = Followup::query()
            ->whereBetween('date', [
                Carbon::today()->startOfDay(),
                Carbon::today()->endOfDay()
            ])
            ->where('status', 1)
            // 只能看自己
            ->when(!user()->hasAnyAccess(['superuser', 'followup.view.all']), function (Builder $query) {
                $query->where('followup.followup_user', user()->id);
            })
            ->count();

        // 今天生日
        $birthday = Customer::query()
            ->whereBetween(DB::raw("DATE_FORMAT( birthday, '%m-%d' )"), [
                date('m-d'),
                date('m-d')
            ])
            ->when(!user()->hasAnyAccess(['superuser', 'customer.view.all']), function (Builder $query) {
                $ids = user()->getCustomerViewUsersPermission();
                $query->where(function ($query) use ($ids) {
                    $query->whereIn('ascription', $ids)->orWhereIn('consultant', $ids);
                });
            })
            ->whereNotNull('birthday')
            ->count();

        // 今日预约
        $appointment = Appointment::query()
            ->whereBetween('date', [
                Carbon::today()->toDateString(),
                Carbon::today()->toDateString()
            ])
            // 只能看自己
            ->when(!user()->hasAnyAccess(['superuser']), function (Builder $query) {
                $query->where(function ($query) {
                    $query->where('doctor_id', user()->id)
                        ->orWhere('consultant_id', user()->id)
                        ->orWhere('technician_id', user()->id)
                        ->orWhere('create_user_id', user()->id);
                });
            })
            ->count();

        return response_success([
            'appointment' => $appointment,
            'followup'    => $followup,
            'birthday'    => $birthday
        ]);
    }
}
