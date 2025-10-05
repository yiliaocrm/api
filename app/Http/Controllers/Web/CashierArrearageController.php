<?php

namespace App\Http\Controllers\Web;

use App\Exceptions\HisException;
use App\Http\Controllers\Controller;
use App\Http\Requests\CashierArrearage\FreeRequest;
use App\Http\Requests\CashierArrearage\RepaymentRequest;
use App\Models\CashierArrearage;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class CashierArrearageController extends Controller
{
    public function manage(Request $request): JsonResponse
    {
        $rows  = $request->input('rows', 10);
        $sort  = $request->input('sort', 'cashier_arrearage.created_at');
        $order = $request->input('order', 'desc');
        $data  = [];
        $query = CashierArrearage::query()
            ->with([
                'customer:id,name,idcard,balance',
                'details' => function ($query) {
                    $query->orderBy('created_at', 'desc');
                }
            ])
            ->select('cashier_arrearage.*')
            ->leftJoin('customer', 'customer.id', '=', 'cashier_arrearage.customer_id')
            ->when($request->input('created_at_start') && $request->input('created_at_end'), function (Builder $query) use ($request) {
                $query->whereBetween('cashier_arrearage.created_at', [
                    Carbon::parse($request->input('created_at_start')),
                    Carbon::parse($request->input('created_at_end'))->endOfDay()
                ]);
            })
            ->when($request->input('status'), function (Builder $query) use ($request) {
                $query->where('cashier_arrearage.status', $request->input('status'));
            })
            ->when($request->input('keyword'), function (Builder $query) use ($request) {
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
     * 还款操作
     * @param RepaymentRequest $request
     * @return JsonResponse
     * @throws HisException|Throwable
     */
    public function repayment(RepaymentRequest $request): JsonResponse
    {
        DB::beginTransaction();
        try {

            $arrearage = CashierArrearage::query()->find(
                $request->input('id')
            );

            // 创建收费通知单(未付款状态)
            $cashier = $arrearage->cashierable()->create(
                $request->cashierData($arrearage)
            );

            // 写入付款方式
            $cashier->pay()->createMany(
                $request->payData($arrearage)
            );

            // 写入营收明细
            $cashier->details()->create(
                $request->cashierDetailData($cashier, $arrearage)
            );

            // 处理[还款]后各种逻辑
            foreach ($cashier->details as $detail) {
                $request->cashierAfter($detail);
            }

            // 创建还款(明细)记录
            $detail = $arrearage->details()->create(
                $request->detailsData($arrearage, $cashier->id)
            );

            // 更新欠款单
            $arrearage->update(
                $request->updateData($arrearage)
            );

            // 更新收费单(已收费)
            $cashier->update([
                'status' => 2, // 已收费
                'income' => $cashier->pay->sum('income'),
                'detail' => $detail
            ]);

            // 更新[顾客信息]
            $cashier->customer->update(
                $request->customerData($cashier)
            );

            DB::commit();
            return response_success($arrearage);

        } catch (Exception $e) {
            DB::rollBack();
            throw new HisException($e->getMessage());
        }
    }

    /**
     * 免单处理
     * @param FreeRequest $request
     * @return JsonResponse
     * @throws HisException|Throwable
     */
    public function free(FreeRequest $request): JsonResponse
    {
        DB::beginTransaction();
        try {

            $arrearage = CashierArrearage::query()->find(
                $request->input('id')
            );

            // 写入还款明细:免单
            $arrearage->details()->create(
                $request->detailsData($arrearage)
            );

            // 更新项目明细表,欠款信息
            if ($arrearage->product_id) {
                $arrearage->customerProduct->decrement('arrearage', $arrearage->leftover);
            }

            // 更新物品明细表,欠款信息
            if ($arrearage->goods_id) {
                $arrearage->customerGoods->decrement('arrearage', $arrearage->leftover);
            }

            // 更新顾客表,欠款信息
            $arrearage->customer->decrement('arrearage', $arrearage->leftover);

            // 免单状态
            $arrearage->update(['status' => 3]);
            DB::commit();
            return response_success();

        } catch (Exception $e) {
            DB::rollBack();
            throw new HisException($e->getMessage());
        }
    }
}
