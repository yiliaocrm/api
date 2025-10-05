<?php

namespace App\Http\Controllers\Api;

use Exception;
use App\Exceptions\HisException;
use App\Models\Customer;
use App\Models\Reservation;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ReservationInfoRequest;
use App\Http\Requests\Api\ReservationCreateRequest;
use Throwable;

class ReservationController extends Controller
{
    /**
     * 列表
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $rows  = $request->input('rows', 10);
        $sort  = $request->input('sort', 'created_at');
        $order = $request->input('order', 'desc');
        $query = Reservation::query()
            ->select([
                'customer.sex',
                'customer.name',
                'reservation.id',
                'reservation.status',
                'reservation.user_id',
                'reservation.medium_id',
                'reservation.created_at',
            ])
            ->with([
                'user:id,name',
                'medium:id,name',
                'reservationItems:id,name'
            ])
            ->leftJoin('customer', 'customer.id', '=', 'reservation.customer_id')
            ->orderBy($sort, $order)
            ->paginate($rows);

        return response_success([
            'rows'  => $query->items(),
            'total' => $query->total()
        ]);
    }

    /**
     * 查看报单信息
     * @param ReservationInfoRequest $request
     * @return JsonResponse
     */
    public function info(ReservationInfoRequest $request): JsonResponse
    {
        $reservation = Reservation::query()->find(
            $request->input('id')
        );
        $reservation->load([
            'department:id,name',
            'reservationItems:id,name'
        ]);
        return response_success($reservation);
    }

    /**
     * 渠道报单
     * @param ReservationCreateRequest $request
     * @return JsonResponse
     * @throws HisException|Throwable
     */
    public function create(ReservationCreateRequest $request): JsonResponse
    {
        DB::beginTransaction();
        try {

            // [查询]或[创建顾客]
            $customer = Customer::query()->firstOrCreate(
                ['id' => $request->input('customer_id')],
                $request->createCustomerData()
            );

            // 新增[报单记录]
            $reservation = Reservation::query()->create(
                $request->reservationData($customer->id)
            );

            // 关联[咨询项目]
            $reservation->reservationItems()->sync(
                $reservation->items
            );

            // 创建[生命周期]
            $reservation->customerLifeCycle()->create([
                'name'        => '网电咨询',
                'customer_id' => $reservation->customer_id
            ]);

            // 创建[操作日志]
            $reservation->customerLog()->create([
                'customer_id' => $reservation->customer_id
            ]);

            // 沟通记录
            $reservation->customerTalk()->create([
                'name'        => '网电咨询备注',
                'customer_id' => $reservation->customer_id
            ]);

            // 更新[顾客信息]
            $customer->update(
                $request->updateCustomerData($customer)
            );

            DB::commit();
            return response_success($reservation);
        } catch (Exception $e) {
            DB::rollBack();
            throw new HisException($e->getMessage());
        }
    }
}
