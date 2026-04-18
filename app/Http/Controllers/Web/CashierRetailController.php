<?php

namespace App\Http\Controllers\Web;

use App\Enums\CashierRetailStatus;
use App\Exceptions\HisException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\CashierRetailRequest;
use App\Models\CashierRetail;
use App\Models\Customer;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class CashierRetailController extends Controller
{
    public function manage(CashierRetailRequest $request): JsonResponse
    {
        $rows = $request->input('rows', 10);
        $sort = $request->input('sort', 'created_at');
        $order = $request->input('order', 'desc');
        $keyword = $request->input('keyword');
        $date = $request->input('date');
        $filters = $request->input('filters', []);

        $builder = CashierRetail::query()
            ->with(['customer', 'pay', 'user:id,name'])
            ->select('cashier_retail.*')
            ->leftJoin('customer', 'customer.id', '=', 'cashier_retail.customer_id')
            ->whereBetween('cashier_retail.created_at', [
                Carbon::parse($date[0])->startOfDay(),
                Carbon::parse($date[1])->endOfDay(),
            ])
            ->when($keyword, fn (Builder $query) => $query->whereLike('customer.keyword', '%'.$keyword.'%'))
            ->queryConditions('CashierRetailIndex', $filters)
            ->orderBy("cashier_retail.{$sort}", $order);

        $query = $builder->clone()->paginate($rows);

        $query->append(['status_text']);

        $footer = [
            [
                'status' => '页小计:',
                'payable' => collect($query->items())->sum('payable'),
                'income' => collect($query->items())->sum('income'),
                'deposit' => collect($query->items())->sum('deposit'),
                'arrearage' => collect($query->items())->sum('arrearage'),
            ],
            [
                'status' => '总合计:',
                'payable' => floatval($builder->clone()->sum('cashier_retail.payable')),
                'income' => floatval($builder->clone()->sum('cashier_retail.income')),
                'deposit' => floatval($builder->clone()->sum('cashier_retail.deposit')),
                'arrearage' => floatval($builder->clone()->sum('cashier_retail.arrearage')),
            ],
        ];

        return response_success([
            'rows' => $query->items(),
            'total' => $query->total(),
            'footer' => $footer,
        ]);
    }

    /**
     * 填充状态
     */
    public function fill(Request $request): JsonResponse
    {
        $customer = Customer::query()->find(
            $request->input('customer_id')
        );

        return response_success([
            'type' => $customer->receptions->count() > 1 ? 2 : 1,
            'medium_id' => $customer->medium_id,
        ]);
    }

    /**
     * 零售信息
     */
    public function info(CashierRetailRequest $request): JsonResponse
    {
        $cashierRetail = CashierRetail::query()
            ->with([
                'pay',
                'customer:id,name,idcard,balance',
                'details.unit:id,name',
                'details.goods.units.unit:id,name',
            ])
            ->find($request->input('id'));

        $cashierRetail->details->each(function ($detail) {
            $detail->units = $detail->goods?->units?->map(fn ($unit) => [
                'id' => $unit->unit_id,
                'unit_id' => $unit->unit_id,
                'name' => $unit->unit?->name,
                'unit_name' => $unit->unit?->name,
                'retailprice' => $unit->retailprice,
                'basic' => $unit->basic,
            ])?->values()?->all() ?? [];
            $detail->unsetRelation('goods');
        });

        return response_success($cashierRetail);
    }

    /**
     * 零售收费
     *
     * @throws HisException|Throwable
     */
    public function charge(CashierRetailRequest $request): JsonResponse
    {
        DB::beginTransaction();
        try {

            // 更新或者创建
            $retail = CashierRetail::query()->updateOrCreate(
                ['id' => $request->input('id')],
                $request->fillData()
            );

            // 创建收费零售单明细
            // 先删旧明细，再写入新明细
            $retail->details()->delete();
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
                'cashier_id' => $cashier->id,
            ]);

            // 设置为已收费
            $cashier->update(['status' => CashierRetailStatus::DEAL->value]);
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
     */
    public function pending(CashierRetailRequest $request): JsonResponse
    {
        $retail = CashierRetail::query()->updateOrCreate(
            ['id' => $request->input('id')],
            $request->fillData()
        );

        // 先删旧明细，再写入新明细
        $retail->details()->delete();
        $retail->details()->createMany($request->detailsData());

        return response_success($retail);
    }

    /**
     * 删除挂单记录
     */
    public function remove(CashierRetailRequest $request): JsonResponse
    {
        CashierRetail::query()->find($request->input('id'))->delete();

        return response_success();
    }
}
