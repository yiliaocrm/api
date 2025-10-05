<?php

namespace App\Http\Controllers\Web;

use App\Exceptions\HisException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Coupon\CreateRequest;
use App\Http\Requests\Coupon\InfoRequest;
use App\Http\Requests\Coupon\IssueRequest;
use App\Http\Requests\Coupon\RemoveRequest;
use App\Models\CashierCoupon;
use App\Models\Coupon;
use App\Models\CouponDetail;
use App\Models\Customer;
use App\Models\Product;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class CouponController extends Controller
{
    /**
     * 现金券列表
     * @param Request $request
     * @return JsonResponse
     */
    public function manage(Request $request): JsonResponse
    {
        $sort  = $request->input('sort', 'id');
        $order = $request->input('order', 'desc');
        $rows  = $request->input('rows', 10);
        $query = Coupon::query()
            ->with(['createUser:id,name'])
            ->when($request->input('created_at_start') && $request->input('created_at_end'), function (Builder $query) use ($request) {
                $query->whereBetween('created_at', [
                    Carbon::parse($request->input('created_at_start')),
                    Carbon::parse($request->input('created_at_end'))->endOfDay()
                ]);
            })
            ->when($request->input('name'), function (Builder $query) use ($request) {
                $query->where('name', 'like', '%' . $request->input('name') . '%');
            })
            ->when($request->input('status'), function (Builder $query) use ($request) {
                $query->where('status', $request->input('name'));
            })
            ->orderBy($sort, $order)
            ->paginate($rows);

        return response_success([
            'rows'  => $query->items(),
            'total' => $query->total()
        ]);
    }

    /**
     * 创建卡券
     * @param CreateRequest $request
     * @return JsonResponse
     * @throws HisException|Throwable
     */
    public function create(CreateRequest $request): JsonResponse
    {
        DB::beginTransaction();
        try {

            $coupon = Coupon::query()->create(
                $request->formData()
            );

            DB::commit();

            return response_success($coupon);

        } catch (Exception $e) {
            DB::rollBack();
            throw new HisException($e->getMessage());
        }
    }

    /**
     * 发券
     * @param IssueRequest $request
     * @return JsonResponse
     * @throws HisException|Throwable
     */
    public function issue(IssueRequest $request): JsonResponse
    {
        DB::beginTransaction();
        try {

            // 顾客信息
            $customer = Customer::query()->find(
                $request->input('form.customer_id')
            );

            // 卡券项目
            $product = Product::query()->find(2);

            // 活动(卡券)信息
            $coupon = Coupon::query()->find(
                $request->input('form.coupon_id')
            );

            // 发券
            $couponDetail = CouponDetail::query()->create(
                $request->formData($coupon)
            );

            // 创建[收费通知]
            $cashier = $couponDetail->cashierable()->create(
                $request->cashierData($couponDetail)
            );

            // 创建[支付信息]
            $cashier->pay()->createMany(
                $request->payData()
            );

            // 积分换券(扣除积分)
            // @积分变动,需要更新到customer表
            if ($couponDetail->integrals) {
                $customer->integrals()->create(
                    $request->integralsData($couponDetail, $customer)
                );
            }

            // 创建[营收明细]
            $cashier->details()->create(
                $request->cashierDetailData($cashier, $couponDetail)
            );

            // 写入[客户项目明细表]
            $customer->products()->createMany(
                $request->customerProduct($cashier->details, $product)
            );

            // 写入[项目消费积分](比如,卡券需要购买,需要增加积分)
            // @积分变动,没有更新到customer表
            $customer->integrals()->createMany(
                $request->customerIntegrals($cashier->details, $product, $customer)
            );

            // 写入[业绩表]
            $customer->salesPerformances()->createMany(
                $request->salesPerformances($coupon, $cashier, $cashier->details, $product, $customer)
            );

            // 卡券[已领取]+1
            $coupon->increment('issue_count');

            // 更新顾客信息
            $customer->update(
                $request->customerData($customer, $coupon, $couponDetail, $product)
            );

            DB::commit();
            return response_success($couponDetail);

        } catch (Exception $e) {
            DB::rollBack();
            throw new HisException($e->getMessage());
        }
    }

    /**
     * 删除卡券
     * @param RemoveRequest $request
     * @return JsonResponse
     * @throws Exception
     */
    public function remove(RemoveRequest $request): JsonResponse
    {
        Coupon::query()->find($request->input('id'))->delete();
        return response_success();
    }

    /**
     * 发券明细
     * @param InfoRequest $request
     * @return JsonResponse
     */
    public function detail(InfoRequest $request): JsonResponse
    {
        $sort  = $request->input('sort', 'id');
        $order = $request->input('order', 'desc');
        $rows  = $request->input('rows', 10);
        $data  = [];
        $query = CouponDetail::query()
            ->with([
                'createUser:id,name',
                'customer:id,idcard,name'
            ])
            ->where('coupon_id', $request->input('coupon_id'))
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
     * 卡券使用记录
     * @param InfoRequest $request
     * @return JsonResponse
     */
    public function cashier(InfoRequest $request): JsonResponse
    {
        $sort  = $request->input('sort', 'id');
        $order = $request->input('order', 'desc');
        $rows  = $request->input('rows', 10);
        $data  = [];
        $query = CashierCoupon::query()
            ->with([
                'user:id,name',
                'customer:id,idcard,name'
            ])
            ->where('coupon_id', $request->input('coupon_id'))
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
}
