<?php

namespace App\Http\Controllers\Web;

use App\Exceptions\HisException;
use App\Http\Controllers\Controller;
use App\Http\Requests\CashierRetail\ChargeRequest;
use App\Http\Requests\CashierRetail\InfoRequest;
use App\Http\Requests\CashierRetail\PendingRequest;
use App\Http\Requests\CashierRetail\RemoveRequest;
use App\Models\CashierRetail;
use App\Models\Customer;
use Carbon\Carbon;
use Exception;
use Throwable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CashierRetailController extends Controller
{
    public function manage(Request $request): JsonResponse
    {
        $rows  = $request->input('rows', 10);
        $sort  = $request->input('sort', 'created_at');
        $order = $request->input('order', 'desc');
        $query = CashierRetail::with('customer', 'pay')
            ->select('cashier_retail.*')
            ->leftJoin('customer', 'customer.id', '=', 'cashier_retail.customer_id')
            ->when($request->input('created_at_start') && $request->input('created_at_end'), function (Builder $query) use ($request) {
                $query->whereBetween('cashier_retail.created_at', [
                    Carbon::parse($request->input('created_at_start')),
                    Carbon::parse($request->input('created_at_end'))->endOfDay()
                ]);
            })
            ->when($request->input('keyword'), function (Builder $query) use ($request) {
                $query->where('customer.keyword', 'like', '%' . $request->input('keyword') . '%');
            })
            ->when($request->input('status'), function (Builder $query) use ($request) {
                $query->where('cashier_retail.status', $request->input('status'));
            })
            ->when($request->input('type'), function (Builder $query) use ($request) {
                $query->where('cashier_retail.type', $request->input('type'));
            })
            ->when($request->input('user_id'), function (Builder $query) use ($request) {
                $query->where('cashier_retail.user_id', $request->input('user_id'));
            })
            ->when($request->input('remark'), function (Builder $query) use ($request) {
                $query->where('cashier_retail.remark', 'like', '%' . $request->input('remark') . '%');
            })
            ->orderBy($sort, $order)
            ->paginate($rows);

        return response_success([
            'rows'  => $query->items(),
            'total' => $query->total()
        ]);
    }

    /**
     * 填充状态
     * @param Request $request
     * @return JsonResponse
     */
    public function fill(Request $request): JsonResponse
    {
        $customer = Customer::query()->find(
            $request->input('customer_id')
        );

        return response_success([
            'type'      => $customer->receptions->count() > 1 ? 2 : 1,
            'medium_id' => $customer->medium_id
        ]);
    }

    /**
     * 零售信息
     * @param InfoRequest $request
     * @return JsonResponse
     */
    public function info(InfoRequest $request): JsonResponse
    {
        $cashierRetail = CashierRetail::query()->find(
            $request->input('id')
        );
        $cashierRetail->loadMissing([
            'pay',
            'details',
            'customer:id,name,balance'
        ]);
        return response_success($cashierRetail);
    }

    /**
     * 零售收费
     * @param ChargeRequest $request
     * @return JsonResponse
     * @throws HisException|Throwable
     */
    public function charge(ChargeRequest $request): JsonResponse
    {
        DB::beginTransaction();
        try {

            // 更新或者创建
            $retail = CashierRetail::query()->updateOrCreate(
                ['id' => $request->input('id')],
                $request->fillData()
            );

            // 创建收费零售单明细
            $retail->details()->createMany(
                $request->detailsData()
            );

            // 收费通知(未收费状态)
            $cashier = $retail->cashierable()->create(
                $request->cashierData($retail->details)
            );

            // 写入支付信息
            $cashier->pay()->createMany(
                $request->payData()
            );

            // 写入[营收明细]
            $cashier->details()->createMany(
                $request->CashierDetailData($cashier)
            );


            // 写入收费单号
            $retail->update([
                'detail'     => $retail->details()->get(),
                'cashier_id' => $cashier->id
            ]);

            // 设置为已收费
            $cashier->update(['status' => 2]);
            DB::commit();

            // 获取关联数据
            $cashier->load('customer:id,name,idcard,sex,balance', 'pay');

            return response_success($cashier);

        } catch (Exception $e) {
            DB::rollBack();
            throw new HisException($e->getMessage());
        }
    }

    /**
     * 挂单处理
     * @param PendingRequest $request
     * @return JsonResponse
     */
    public function pending(PendingRequest $request): JsonResponse
    {
        $retail = CashierRetail::query()->updateOrCreate(
            ['id' => $request->input('id')],
            $request->fillData()
        );
        return response_success($retail);
    }

    /**
     * 删除挂单记录
     * @param RemoveRequest $request
     * @return JsonResponse
     */
    public function remove(RemoveRequest $request): JsonResponse
    {
        CashierRetail::query()->find($request->input('id'))->delete();
        return response_success();
    }
}
