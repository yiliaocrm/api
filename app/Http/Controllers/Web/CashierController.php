<?php

namespace App\Http\Controllers\Web;

use App\Exceptions\HisException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Cashier\CancelRequest;
use App\Http\Requests\Cashier\ChargeRequest;
use App\Http\Requests\Cashier\ConsultantChargeRequest;
use App\Http\Requests\Cashier\ErkaiChargeRequest;
use App\Http\Requests\Cashier\InfoRequest;
use App\Http\Requests\Cashier\RechargeRequest;
use App\Http\Requests\Cashier\RefundChargeRequest;
use App\Models\Cashier;
use App\Models\CashierDetail;
use App\Models\ErkaiDetail;
use App\Models\ReceptionOrder;
use App\Models\Recharge;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class CashierController extends Controller
{
    /**
     * 收银概况
     * @deprecated
     * @param Request $request
     * @return JsonResponse
     */
    public function dashboard(Request $request): JsonResponse
    {
        $income = CashierDetail::query()
            ->whereBetween('created_at', [
                Carbon::parse($request->input('start')),
                Carbon::parse($request->input('end'))->endOfDay(),
            ])
            ->sum('income');

        // 营业额:扣掉当天退款,包含当天余额支付,不包含当天充值
        $turnover = CashierDetail::query()
            ->whereBetween('created_at', [
                Carbon::parse($request->input('start')),
                Carbon::parse($request->input('end'))->endOfDay(),
            ])
            ->where(function ($query) {
                $query->where('product_id', '<>', 1)->orWhereNull('product_id');
            })
            ->sum(DB::raw('income + deposit'));

        $deposit = CashierDetail::query()
            ->whereBetween('created_at', [
                Carbon::parse($request->input('start')),
                Carbon::parse($request->input('end'))->endOfDay(),
            ])
            ->sum('deposit');

        $refund = CashierDetail::query()
            ->whereBetween('created_at', [
                Carbon::parse($request->input('start')),
                Carbon::parse($request->input('end'))->endOfDay(),
            ])
            ->where('cashierable_type', 'App\Models\CashierRefund')
            ->sum('income');

        return response_success(
            [
                'dashboard' => [
                    'income'   => $income,
                    'deposit'  => $deposit,
                    'turnover' => $turnover,
                    'refund'   => abs($refund)
                ]
            ]
        );
    }

    /**
     * 收费列表
     * @return JsonResponse
     */
    public function manage(): JsonResponse
    {
        $sort    = request('sort', 'cashier.created_at');
        $order   = request('order', 'desc');
        $rows    = request('rows', 10);
        $builder = Cashier::query()
            ->with([
                'pay',
                'customer:id,name,idcard,sex,balance',
                'customer.phones',
                'cashierCoupon',
                'customerCouponDetail'
            ])
            ->select('cashier.*')
            ->leftJoin('customer', 'customer.id', '=', 'cashier.customer_id')
            ->when(request('created_at_start') && request('created_at_end'), function (Builder $query) {
                $query->whereBetween('cashier.created_at', [
                    Carbon::parse(request('created_at_start')),
                    Carbon::parse(request('created_at_end'))->endOfDay()
                ]);
            })
            ->when(request('updated_at_start') && request('updated_at_end'), function (Builder $query) {
                $query->whereBetween('cashier.updated_at', [
                    Carbon::parse(request('updated_at_start')),
                    Carbon::parse(request('updated_at_end'))->endOfDay()
                ]);
            })
            ->when(request('id'), function (Builder $query) {
                $query->where('cashier.id', request('id'));
            })
            ->when(request('keyword'), function (Builder $query) {
                $query->where('customer.keyword', 'like', '%' . request('keyword') . '%');
            })
            ->when(request('cashierable_type'), function (Builder $query) {
                $query->where('cashier.cashierable_type', request('cashierable_type'));
            })
            ->when(request('status'), function (Builder $query) {
                $query->where('cashier.status', request('status'));
            })
            ->when(request('operator'), function (Builder $query) {
                $query->where('cashier.operator', request('operator'));
            })
            ->when(request('department_id'), function (Builder $query) {
                $query->leftJoin('users', 'users.id', '=', 'cashier.user_id')
                    ->where('users.department_id', request('department_id'));
            })
            ->when(request('user_id'), function (Builder $query) {
                $query->where('cashier.user_id', request('user_id'));
            })
            ->when(request('key'), function (Builder $query) {
                $query->where('cashier.key', 'like', '%' . request('key') . '%');
            })
            ->when(!user()->hasAnyAccess(['superuser', 'cashier.view.all']), function (Builder $query) {
                $ids = user()->getUserIdsForCashier();
                $query->where(function ($query) use ($ids) {
                    $query->whereIn('cashier.user_id', $ids)->orWhereIn('cashier.operator', $ids);
                });
            })
            ->orderBy($sort, $order);

        $query  = $builder->clone()->paginate($rows);
        $footer = [
            [
                'phone'     => '页小计:',
                'payable'   => collect($query->items())->sum('payable'),
                'income'    => collect($query->items())->sum('income'),
                'deposit'   => collect($query->items())->sum('deposit'),
                'coupon'    => collect($query->items())->sum('coupon'),
                'arrearage' => collect($query->items())->sum('arrearage'),
            ],
            [
                'phone'     => '总合计:',
                'payable'   => floatval($builder->clone()->sum('cashier.payable')),
                'income'    => floatval($builder->clone()->sum('cashier.income')),
                'deposit'   => floatval($builder->clone()->sum('cashier.deposit')),
                'coupon'    => floatval($builder->clone()->sum('cashier.coupon')),
                'arrearage' => floatval($builder->clone()->sum('cashier.arrearage')),
            ]
        ];

        return response_success([
            'rows'   => $query->items(),
            'total'  => $query->total(),
            'footer' => $footer
        ]);
    }

    /**
     * 收费单信息
     * @param InfoRequest $request
     * @return JsonResponse
     */
    public function info(InfoRequest $request): JsonResponse
    {
        $cashier = Cashier::query()->find(
            $request->input('id')
        );
        $cashier->loadMissing([
            'pay',
            'cashierCoupon',
            'customer:id,balance',
            'customerCouponDetail',
        ]);
        return response_success($cashier);
    }

    /**
     * 现场咨询收费单
     * @param ConsultantChargeRequest $request
     * @return JsonResponse
     * @throws HisException|Throwable
     */
    public function consultantCharge(ConsultantChargeRequest $request): JsonResponse
    {
        DB::beginTransaction();
        try {

            // 查询收费通知单
            $cashier = Cashier::query()->find(
                $request->input('id')
            );

            // 顾客信息
            $customer = $cashier->customer;

            // 允许收银员修改收费单
            if (parameter('cashier_allow_modify')) {
                $changes = $request->getDetailChanges($cashier);
                $ids     = [];

                // 删除项目
                if (count($changes['deleted'])) {
                    $cashier->cashierable->orders()->whereIn('id', $changes['deleted'])->delete();
                }

                // 新增项目
                if (count($changes['inserted'])) {
                    $ids = collect($cashier->cashierable->orders()->createMany(
                        $changes['inserted']
                    ))->pluck('id')->toArray();
                }

                // 更新项目
                if (count($changes['updated'])) {
                    foreach ($changes['updated'] as $order) {
                        $ids[] = $order['id'];
                        ReceptionOrder::query()->find($order['id'])->update($order);
                    }
                }

                // 更新收费通知单
                if (count($changes['inserted']) || count($changes['updated'])) {
                    $detail           = $cashier->cashierable->orders()->whereIn('id', $ids)->get();
                    $cashier->detail  = $detail;
                    $cashier->payable = $detail->sum('payable');
                    $cashier->save();
                }
            }

            // 写入支付信息
            $cashier->pay()->createMany($request->payData(
                $customer->id
            ));

            // 写入用券信息
            $cashier->cashierCoupon()->createMany(
                $request->cashierCouponData($customer->id)
            );

            // 写入[营收明细]
            $cashier->details()->createMany(
                $request->CashierDetailData($cashier)
            );

            // 写入[营收明细]后,各种更新
            foreach ($cashier->details as $detail) {
                $request->handleCashierDetail($detail, $customer);
            }

            // 更新[收费通知单]状态
            $cashier->update(
                $request->cashierData($cashier)
            );

            // 更新[收费通知单]后,各种更新
            $request->handleCashier($cashier, $customer);

            foreach ($cashier->cashierCoupon as $cashierCoupon) {
                // 写入[卡券]变动明细
                $cashier->couponDetailHistory()->create(
                    $request->couponDetailHistoryData($cashierCoupon)
                );
                // 更新[卡券]信息
                $cashierCoupon->couponDetail->update(
                    $request->couponDetailData($cashierCoupon)
                );
            }

            DB::commit();

            // 获取关联数据
            $cashier->load([
                'pay',
                'customer:id,name,idcard,sex,balance',
                'cashierCoupon',
                'customerCouponDetail'
            ]);

            return response_success($cashier);

        } catch (Exception $e) {
            DB::rollBack();
            throw new HisException($e->getMessage());
        }
    }

    /**
     * 二开收费
     * @param ErkaiChargeRequest $request
     * @return JsonResponse
     */
    public function erkaiCharge(ErkaiChargeRequest $request): JsonResponse
    {
        DB::beginTransaction();
        try {

            // 查询收费通知单
            $cashier = Cashier::query()->find(
                $request->input('id')
            );

            // 顾客信息
            $customer = $cashier->customer;

            // 允许收银员修改收费单(以下功能有bug)暂未实现
            if (parameter('cashier_allow_modify')) {
//                $changes = $request->getDetailChanges($cashier);
//                $ids     = [];
//                $detail  = [];
//
//                // 删除项目
//                if (count($changes['deleted'])) {
//                    $cashier->cashierable->details()->whereIn('id', $changes['deleted'])->delete();
//                }
//
//                // 新增项目
//                if (count($changes['inserted'])) {
//                    $ids = collect($cashier->cashierable->details()->createMany(
//                        $changes['inserted']
//                    ))->pluck('id')->toArray();
//                }
//
//                // 更新项目
//                if (count($changes['updated'])) {
//                    foreach ($changes['updated'] as $order) {
//                        $ids[] = $order['id'];
//                        ErkaiDetail::query()->find($order['id'])->update($order);
//                    }
//                }
//
//                // 更新收费通知单
//                if (count($changes['inserted']) || count($changes['updated'])) {
//                    $detail           = $cashier->cashierable->details()->whereIn('id', $ids)->get();
//                    $cashier->detail  = $detail;
//                    $cashier->payable = $detail->sum('payable');
//                    $cashier->save();
//                }
            }

            // 写入支付信息
            $cashier->pay()->createMany($request->payData(
                $customer->id
            ));

            // 写入[营收明细]
            $cashier->details()->createMany(
                $request->CashierDetailData($cashier)
            );

            // 写入[营收明细]后,处理各种业务
            foreach ($cashier->details as $detail) {
                $request->handleCashierDetail($detail, $customer);
            }

            // 更新[收费通知单]状态
            $cashier->update(
                $request->cashierData($cashier)
            );

            // 更新[收费通知单]后,处理各种业务
            $request->handleCashier($cashier, $customer);

            DB::commit();

            // 获取关联数据
            $cashier->load([
                'pay',
                'customer:id,name,idcard,sex,balance'
            ]);

            return response_success($cashier);

        } catch (Exception $e) {
            DB::rollBack();
            throw new HisException($e->getMessage());
        }
    }

    /**
     * 退款收费
     * @param RefundChargeRequest $request
     * @return JsonResponse
     * @throws HisException|Throwable
     */
    public function refundCharge(RefundChargeRequest $request): JsonResponse
    {
        DB::beginTransaction();
        try {

            // 查询收费通知单
            $cashier = Cashier::query()->find(
                $request->input('id')
            );

            // 顾客信息
            $customer = $cashier->customer;

            // 写入支付信息
            $cashier->pay()->createMany($request->payData(
                $cashier->customer_id
            ));

            // 写入[营收明细]
            $cashier->details()->createMany(
                $request->CashierDetailData($cashier)
            );

            // 写入[营收明细]后,处理各种业务.
            foreach ($cashier->details as $detail) {
                $request->handleCashierDetail($cashier, $detail, $customer);
            }

            // 更新[收费通知单]状态
            $cashier->update(
                $request->cashierData($cashier)
            );

            // 更新[收费通知单]后,处理各种业务
            $request->handleCashier($cashier, $customer);

            DB::commit();

            // 获取关联数据
            $cashier->load([
                'pay',
                'customer:id,name,idcard,sex,balance'
            ]);

            return response_success($cashier);

        } catch (Exception $e) {
            DB::rollBack();
            throw new HisException($e->getMessage());
        }
    }

    /**
     * 收费操作
     * @param ChargeRequest $request
     * @return JsonResponse
     * @throws HisException|Throwable
     */
    public function charge(ChargeRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            // 查询收费通知单
            $cashier = Cashier::query()->find(
                $request->input('id')
            );

            // 允许修改的单据类型
            $modifyable = ['App\Models\Consultant', 'App\Models\Erkai'];

            // 允许收银员修改收费单
            if (parameter('cashier_allow_modify') && in_array($cashier->cashierable_type, $modifyable)) {
                $changes = $request->getDetailChanges($cashier);
                $ids     = [];
                $detail  = [];

                // 删除项目
                if (count($changes['deleted'])) {
                    // 现场咨询业务
                    if ($cashier->cashierable_type == 'App\Models\Consultant') {
                        $cashier->cashierable->orders()->whereIn('id', $changes['deleted'])->delete();
                    }
                    // 二开业务
                    if ($cashier->cashierable_type == 'App\Models\Erkai') {
                        $cashier->cashierable->details()->whereIn('id', $changes['deleted'])->delete();
                    }
                }

                // 新增项目
                if (count($changes['inserted'])) {
                    // 现场咨询业务
                    if ($cashier->cashierable_type == 'App\Models\Consultant') {
                        $ids = collect($cashier->cashierable->orders()->createMany(
                            $changes['inserted']
                        ))->pluck('id')->toArray();
                    }
                    // 二开业务
                    if ($cashier->cashierable_type == 'App\Models\Erkai') {
                        $ids = collect($cashier->cashierable->details()->createMany(
                            $changes['inserted']
                        ))->pluck('id')->toArray();
                    }
                }

                // 更新项目
                if (count($changes['updated'])) {
                    // 现场咨询业务
                    if ($cashier->cashierable_type == 'App\Models\Consultant') {
                        foreach ($changes['updated'] as $order) {
                            $ids[] = $order['id'];
                            ReceptionOrder::query()->find($order['id'])->update($order);
                        }
                    }
                    // 二开业务
                    if ($cashier->cashierable_type == 'App\Models\Erkai') {
                        foreach ($changes['updated'] as $order) {
                            $ids[] = $order['id'];
                            ErkaiDetail::query()->find($order['id'])->update($order);
                        }
                    }
                }

                // 更新收费通知单
                if (count($changes['inserted']) || count($changes['updated'])) {
                    // 现场咨询业务
                    if ($cashier->cashierable_type == 'App\Models\Consultant') {
                        $detail = $cashier->cashierable->orders()->whereIn('id', $ids)->get();
                    }
                    // 二开业务
                    if ($cashier->cashierable_type == 'App\Models\Erkai') {
                        $detail = $cashier->cashierable->details()->whereIn('id', $ids)->get();
                    }

                    $cashier->detail  = $detail;
                    $cashier->payable = $detail->sum('payable');
                    $cashier->save();
                }
            }

            // 写入支付信息
            $cashier->pay()->createMany($request->payData(
                $cashier->customer_id
            ));

            // 写入[营收明细]
            $cashier->details()->createMany(
                $request->CashierDetailData($cashier)
            );

            // 更新[收费通知单]状态
            $cashier->update(
                $request->cashierData($cashier)
            );

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
     * 收费明细
     * @param Request $request
     * @return JsonResponse
     */
    public function details(Request $request): JsonResponse
    {
        $details = Cashier::query()
            ->find($request->input('id'))
            ->details;
        return response_success($details);
    }

    /**
     * 充值操作
     * @param RechargeRequest $request
     * @return JsonResponse
     * @throws HisException|Throwable
     */
    public function recharge(RechargeRequest $request): JsonResponse
    {
        DB::beginTransaction();
        try {

            // 创建[充值记录]
            $recharge = Recharge::query()->create(
                $request->fillData()
            );

            // 创建[收费通知]
            $cashier = $recharge->cashierable()->create(
                $request->cashierData()
            );

            // 创建[支付信息]
            $cashier->pay()->createMany(
                $request->payData()
            );

            // 创建[营收明细]
            $cashier->details()->create(
                $request->cashierDetailData($cashier, $recharge)
            );

            // 创建[营收明细]后,处理各种业务逻辑
            foreach ($cashier->details as $detail) {
                $request->handleCashierDetail($cashier, $detail);
            }

            // 写入收费单号
            $recharge->update([
                'cashier_id' => $cashier->id
            ]);

            DB::commit();
            return response_success($recharge);

        } catch (Exception $e) {
            DB::rollBack();
            throw new HisException($e->getMessage());
        }
    }

    /**
     * 业务退单
     * @param CancelRequest $request
     * @return JsonResponse
     * @throws HisException|Throwable
     */
    public function cancel(CancelRequest $request): JsonResponse
    {
        DB::beginTransaction();
        try {

            $cashier = Cashier::query()->find(
                $request->input('id')
            );

            // 退单处理
            $cashier->update(
                $request->updateData($cashier)
            );

            DB::commit();

            return response_success($cashier);

        } catch (Exception $e) {
            DB::rollBack();
            throw new HisException($e->getMessage());
        }
    }
}
