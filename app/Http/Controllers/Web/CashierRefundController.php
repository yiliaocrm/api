<?php

namespace App\Http\Controllers\Web;

use App\Exceptions\HisException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\CashierRefundRequest;
use App\Models\CashierRefund;
use App\Models\CustomerGoods;
use App\Models\CustomerProduct;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Throwable;

class CashierRefundController extends Controller
{
    public function manage(CashierRefundRequest $request): JsonResponse
    {
        $sort = $request->input('sort', 'cashier_refund.created_at');
        $order = $request->input('order', 'desc');
        $rows = $request->input('rows', 10);
        $keyword = $request->input('keyword');

        $query = CashierRefund::query()
            ->with([
                'customer:id,idcard,name',
                'user:id,name',
                'details' => fn ($builder) => $builder
                    ->with(['department:id,name'])
                    ->orderBy('created_at', 'asc'),
            ])
            ->select('cashier_refund.*')
            ->leftJoin('customer', 'customer.id', '=', 'cashier_refund.customer_id')
            ->whereBetween('cashier_refund.created_at', [
                Carbon::parse($request->input('date.0'))->startOfDay(),
                Carbon::parse($request->input('date.1'))->endOfDay(),
            ])
            ->when($keyword, fn (Builder $query) => $query->whereLike('customer.keyword', '%'.$keyword.'%'))
            ->queryConditions('CashierRefundIndex')
            ->orderBy($sort, $order)
            ->paginate($rows);

        $query->append(['status_text']);

        return response_success([
            'rows' => $query->items(),
            'total' => $query->total(),
        ]);
    }

    /**
     * 退款申请单(目前没有审核之前到收费)
     *
     * @throws HisException|Throwable
     */
    public function create(CashierRefundRequest $request): JsonResponse
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
                'status' => 1,
                'payable' => -1 * abs($refund->amount),     // 应付金额
                'income' => 0,                             // 实收金额(不包含余额支付)
                'deposit' => 0,                             // 余额支付
                'arrearage' => 0,                             // 本单欠款金额
                'user_id' => user()->id,
                'detail' => $refund->details,
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
     *
     *
     * @throws HisException|Throwable
     */
    public function remove(CashierRefundRequest $request): JsonResponse
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

    /**
     * 获取顾客已购项目列表（用于退款候选）
     */
    public function products(CashierRefundRequest $request): JsonResponse
    {
        $rows = $request->input('rows', 10);
        $sort = $request->input('sort', 'customer_product.created_at');
        $order = $request->input('order', 'desc');

        $query = CustomerProduct::query()
            ->with([
                'product:id,name',
                'department:id,name',
                'user:id,name',
                'consultantUser:id,name',
                'doctorUser:id,name',
                'ekUserRelation:id,name',
            ])
            ->where('customer_id', $request->input('customer_id'))
            ->where('cashier_id', '!=', 0)
            ->whereNotNull('cashier_id')
            ->orderBy($sort, $order)
            ->paginate($rows);

        return response_success([
            'rows' => $query->items(),
            'total' => $query->total(),
        ]);
    }

    /**
     * 获取顾客已购物品列表（用于退款候选）
     */
    public function goods(CashierRefundRequest $request): JsonResponse
    {
        $rows = $request->input('rows', 10);
        $sort = $request->input('sort', 'customer_goods.created_at');
        $order = $request->input('order', 'desc');

        $query = CustomerGoods::query()
            ->with([
                'department:id,name',
                'user:id,name',
                'consultantUser:id,name',
                'doctorUser:id,name',
                'ekUserRelation:id,name',
            ])
            ->where('customer_id', $request->input('customer_id'))
            ->where('cashier_id', '!=', 0)
            ->whereNotNull('cashier_id')
            ->orderBy($sort, $order)
            ->paginate($rows);

        return response_success([
            'rows' => $query->items(),
            'total' => $query->total(),
        ]);
    }
}
