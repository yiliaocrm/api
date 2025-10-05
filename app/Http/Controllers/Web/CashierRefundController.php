<?php

namespace App\Http\Controllers\Web;

use App\Exceptions\HisException;
use App\Http\Controllers\Controller;
use App\Http\Requests\CashierRefund\CreateRequest;
use App\Http\Requests\CashierRefund\RemoveRequest;
use App\Models\CashierRefund;
use Carbon\Carbon;
use Exception;
use Throwable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CashierRefundController extends Controller
{
    public function manage(Request $request)
    {
        $sort  = request('sort', 'created_at');
        $order = request('order', 'desc');
        $rows  = request('rows', 10);
        $data  = [];

        $query = CashierRefund::query()
            ->with(['customer:id,idcard,name'])
            ->select('cashier_refund.*')
            ->leftJoin('customer', 'customer.id', '=', 'cashier_refund.customer_id')
            ->when($request->input('created_at_start') && $request->input('created_at_end'), function ($query) use ($request) {
                $query->whereBetween('cashier_refund.created_at', [
                    Carbon::parse($request->input('created_at_start')),
                    Carbon::parse($request->input('created_at_end'))->endOfDay()
                ]);
            })
            ->when($request->input('keyword'), function ($query) use ($request) {
                $query->where('customer.keyword', 'like', '%' . $request->input('keyword') . '%');
            })
            ->orderBy($sort, $order)
            ->paginate($rows);

        if ($query) {
            $data['rows']  = $query->items();
            $data['total'] = $query->total();
        } else {
            $data['rows']  = [];
            $data['total'] = 0;
        }

        return response_success($data);
    }

    /**
     * 退款申请单(目前没有审核之前到收费)
     * @param CreateRequest $request
     * @return JsonResponse
     * @throws HisException|Throwable
     */
    public function create(CreateRequest $request): JsonResponse
    {
        DB::beginTransaction();
        try {

            // 创建退款申请单
            $refund = CashierRefund::query()->create(
                $request->formData()
            );

            // 创建退款申请明细
            $refund->details()->createMany(
                $request->detailData($refund)
            );

            // 创建收费通知
            $refund->cashierable()->create([
                'customer_id' => $refund->customer_id,
                'status'      => 1,
                'payable'     => -1 * abs($refund->amount),     // 应付金额
                'income'      => 0,                             // 实收金额(不包含余额支付)
                'deposit'     => 0,                             // 余额支付
                'arrearage'   => 0,                             // 本单欠款金额
                'user_id'     => user()->id,
                'detail'      => $refund->details
            ]);

            DB::commit();

            return response_success($refund);

        } catch (Exception $e) {
            DB::rollBack();
            throw new HisException($e->getMessage());
        }
    }

    /**
     * 删除退款申请单
     * @param RemoveRequest $request
     * @return JsonResponse
     * @throws HisException|Throwable
     */
    public function remove(RemoveRequest $request)
    {
        DB::beginTransaction();
        try {

            $refund = CashierRefund::query()->find(
                $request->input('id')
            );

            $refund->details()->delete();
            $refund->delete();

            DB::commit();

            return response_success();

        } catch (Exception $e) {
            DB::rollBack();
            throw new HisException($e->getMessage());
        }
    }
}
