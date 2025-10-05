<?php

namespace App\Http\Controllers\Web;

use Exception;
use Throwable;
use Carbon\Carbon;
use App\Models\Item;
use App\Models\User;
use App\Models\Medium;
use App\Models\Reception;
use App\Models\Reservation;
use App\Exceptions\HisException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\ReservationRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Builder;

class ReservationController extends Controller
{
    /**
     * 网电咨询记录
     * @param Request $request
     * @return JsonResponse
     */
    public function manage(Request $request): JsonResponse
    {
        $rows  = $request->input('rows', 10);
        $sort  = $request->input('sort', 'reservation.created_at');
        $order = $request->input('order', 'desc');
        $query = Reservation::query()
            ->select('reservation.*', 'customer.name')
            ->leftJoin('customer', 'customer.id', '=', 'reservation.customer_id')
            // 受理日期
            ->when($request->input('reservation_date_start') && $request->input('reservation_date_end'), function ($query) use ($request) {
                $query->whereBetween('reservation.date', [
                    Carbon::parse($request->input('reservation_date_start')),
                    Carbon::parse($request->input('reservation_date_end'))->endOfDay()
                ]);
            })
            // 预约日期
            ->when($request->input('reservation_time_start') && $request->input('reservation_time_end'), function ($query) use ($request) {
                $query->whereBetween('reservation.time', [
                    Carbon::parse($request->input('reservation_time_start')),
                    Carbon::parse($request->input('reservation_time_end'))->endOfDay()
                ]);
            })
            // 上门日期
            ->when($request->input('reservation_cometime_start') && $request->input('reservation_cometime_end'), function ($query) use ($request) {
                $query->whereBetween('reservation.cometime', [
                    Carbon::parse($request->input('reservation_cometime_start')),
                    Carbon::parse($request->input('reservation_cometime_end'))->endOfDay()
                ]);
            })
            // 登记日期
            ->when($request->input('reservation_created_at_start') && $request->input('reservation_created_at_end'), function ($query) use ($request) {
                $query->whereBetween('reservation.created_at', [
                    Carbon::parse($request->input('reservation_created_at_start')),
                    Carbon::parse($request->input('reservation_created_at_end'))->endOfDay()
                ]);
            })
            // 客户信息
            ->when($request->input('customer_keyword'), function ($query) use ($request) {
                $query->where('customer.keyword', 'like', '%' . $request->input('customer_keyword') . '%');
            })
            // 开发人员
            ->when($request->input('customer_ascription'), function ($query) use ($request) {
                $query->where('customer.ascription', $request->input('customer_ascription'));
            })
            // 受理类型
            ->when($request->input('reservation_type'), function ($query) use ($request) {
                $query->where('reservation.type', $request->input('reservation_type'));
            })
            // 咨询科室
            ->when($request->input('reservation_department_id'), function ($query) use ($request) {
                $query->where('reservation.department_id', request('reservation_department_id'));
            })
            // 受理部门
            ->when($request->input('reservation_department_id2'), function ($query) use ($request) {
                $query->leftJoin('users', 'users.id', '=', 'reservation.ascription')->where('users.department_id', $request->input('reservation_department_id2'));
            })
            // 媒介来源
            ->when($request->input('reservation_medium_id'), function ($query) use ($request) {
                $query->whereIn('reservation.medium_id', Medium::query()->find($request->input('reservation_medium_id'))->getAllChild()->pluck('id'));
            })
            // 咨询项目
            ->when($request->input('reservation_items'), function ($query) use ($request) {
                $query->leftJoin('reservation_items', 'reservation.id', '=', 'reservation_items.reservation_id')
                    ->whereIn('reservation_items.item_id', Item::query()->find($request->input('reservation_items'))->getAllChild()->pluck('id'));
            })
            // 咨询人员
            ->when($request->input('reservation_ascription'), function ($query) use ($request) {
                $query->where('reservation.ascription', $request->input('reservation_ascription'));
            })
            // 状态（是否上门）
            ->when($request->input('reservation_status'), function ($query) use ($request) {
                $query->where('reservation.status', $request->input('reservation_status'));
            })
            // 咨询备注
            ->when($request->input('reservation_remark'), function ($query) use ($request) {
                $query->where('reservation.remark', 'like', '%' . $request->input('reservation_remark') . '%');
            })
            // 权限过滤
            ->when(!user()->hasAnyAccess(['superuser', 'reservation.view.all']), function ($query) {
                // 允许查看、指定咨询的挂号数据(开发人是他们的也行)。
                $ids = user()->getReservationViewUsersPermission();
                $query->where(function ($query) use ($ids) {
                    $query->whereIn('reservation.ascription', $ids)->orWhereIn('customer.ascription', $ids);
                });
            })
            ->orderBy($sort, $order)
            ->paginate($rows);

        return response_success([
            'rows'  => $query->items(),
            'total' => $query->total(),
        ]);
    }

    /**
     * 新增咨询
     * @param ReservationRequest $request
     * @return JsonResponse
     * @throws HisException|Throwable
     */
    public function create(ReservationRequest $request): JsonResponse
    {
        DB::beginTransaction();
        try {

            $reservation = Reservation::query()->create(
                $request->formData()
            );

            DB::commit();
            return response_success($reservation);
        } catch (Exception $e) {
            DB::rollBack();
            throw new HisException($e->getMessage());
        }
    }

    /**
     * 更新记录
     * @param ReservationRequest $request
     * @return JsonResponse
     * @throws HisException|Throwable
     */
    public function update(ReservationRequest $request): JsonResponse
    {
        DB::beginTransaction();
        try {

            $reservation = Reservation::query()->find(
                $request->input('id')
            );

            $reservation->update(
                $request->formData()
            );

            DB::commit();

            return response_success($reservation);
        } catch (Exception $e) {
            DB::rollBack();
            throw new HisException($e->getMessage());
        }
    }

    /**
     * 删除咨询
     * @param ReservationRequest $request
     * @return JsonResponse
     * @throws HisException|Throwable
     */
    public function remove(ReservationRequest $request): JsonResponse
    {
        DB::beginTransaction();
        try {

            Reservation::query()->find($request->input('id'))->delete();

            DB::commit();
            return response_success();
        } catch (Exception $e) {
            DB::rollBack();
            throw new HisException($e->getMessage());
        }
    }

    /**
     * 查看咨询
     * @param ReservationRequest $request
     * @return JsonResponse
     */
    public function info(ReservationRequest $request): JsonResponse
    {
        $reservation = Reservation::query()->find(
            $request->input('id')
        );
        return response_success($reservation);
    }

    /**
     * 到院查询
     * @param ReservationRequest $request
     * @return JsonResponse
     */
    public function reception(ReservationRequest $request): JsonResponse
    {
        $rows  = $request->input('rows', 10);
        $sort  = $request->input('sort', 'reception.created_at');
        $order = $request->input('order', 'desc');
        $query = Reception::query()
            ->select([
                'reception.*',
                'customer.name',
                'customer.ascription'
            ])
            ->join('customer', 'customer.id', '=', 'reception.customer_id')
            // 到院时间
            ->when($request->input('created_at_start') && $request->input('created_at_end'), function (Builder $query) use ($request) {
                $query->whereBetween('reception.created_at', [
                    Carbon::parse($request->input('created_at_start')),
                    Carbon::parse($request->input('created_at_end'))->endOfDay()
                ]);
            })
            // 关键词(保留)
            ->when($request->input('keyword'), function (Builder $query) use ($request) {
                $query->where('customer.keyword', 'like', "%{$request->input('keyword')}%");
            })
            // 接待咨询师
            ->when($request->input('consultant'), function (Builder $query) use ($request) {
                $query->where('reception.consultant', $request->input('consultant'));
            })
            // 助诊医生
            ->when($request->input('doctor'), function (Builder $query) use ($request) {
                $query->where('reception.doctor', $request->input('doctor'));
            })
            // 二开人员
            ->when($request->input('ek_user'), function (Builder $query) use ($request) {
                $query->where('reception.ek_user', $request->input('ek_user'));
            })
            // 录单人员
            ->when($request->input('user_id'), function (Builder $query) use ($request) {
                $query->where('reception.user_id', $request->input('user_id'));
            })
            // 接待人员
            ->when($request->input('reception'), function (Builder $query) use ($request) {
                $query->where('reception.reception', $request->input('reception'));
            })
            // 开发人员
            ->when($request->input('ascription'), function (Builder $query) use ($request) {
                $query->where('customer.ascription', $request->input('ascription'));
            })
            // 媒介来源
            ->when($request->input('medium_id'), function (Builder $query) use ($request) {
                $query->whereIn('reception.medium_id', Medium::query()->find($request->input('medium_id'))->getAllChild()->pluck('id'));
            })
            // 咨询项目
            ->when($request->input('items'), function (Builder $query) use ($request) {
                $query->leftJoin('reception_items', 'reception.id', '=', 'reception_items.reception_id')
                    ->whereIn('reception_items.item_id', Item::query()->find($request->input('items'))->getAllChild()->pluck('id'));
            })
            // 咨询科室
            ->when($request->input('department_id'), function (Builder $query) use ($request) {
                $query->where('reception.department_id', $request->input('department_id'));
            })
            // 接诊类型
            ->when($request->input('type'), function (Builder $query) use ($request) {
                $query->where('reception.type', $request->input('type'));
            })
            // 成交状态
            ->when($request->input('status'), function (Builder $query) use ($request) {
                $query->where('reception.status', $request->input('status'));
            })
            // 开发部门
            ->when($request->input('ascription_department'), function (Builder $query) use ($request) {
                $query->whereIn('customer.ascription', User::query()->where('department_id', $request->input('ascription_department'))->get()->pluck('id'));
            })
            // 权限判断
            ->when(!user()->hasAnyAccess(['superuser', 'reservation.view.all']), function (Builder $query) {
                // 允许查看、指定咨询的挂号数据(开发人是他们的也行)。
                $ids = user()->getReservationViewUsersPermission();
                $query->where(function ($query) use ($ids) {
                    $query->whereIn('customer.ascription', $ids);
                });
            })
            ->orderBy($sort, $order)
            ->paginate($rows);

        return response_success([
            'rows'  => $query->items(),
            'total' => $query->total(),
        ]);
    }

    /**
     * 预约上门查询
     * @param Request $request
     * @return JsonResponse
     */
    public function reminder(Request $request): JsonResponse
    {
        $rows  = $request->input('rows', 10);
        $sort  = $request->input('sort', 'reservation.created_at');
        $order = $request->input('order', 'desc');
        $query = Reservation::query()
            ->with(['customer:id,idcard,name'])
            ->whereNotNull('time')
            ->when($request->input('time_start') && $request->input('time_end'), function (Builder $query) use ($request) {
                $query->whereBetween('reservation.time', [
                    Carbon::parse($request->input('time_start')),
                    Carbon::parse($request->input('time_end'))->endOfDay()
                ]);
            })
            ->orderBy($sort, $order)
            ->paginate($rows);

        return response_success([
            'rows'  => $query->items(),
            'total' => $query->total(),
        ]);
    }
}
