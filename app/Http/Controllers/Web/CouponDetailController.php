<?php

namespace App\Http\Controllers\Web;

use App\Enums\CouponDetailStatus;
use App\Exceptions\HisException;
use App\Http\Controllers\Controller;
use App\Models\CouponDetail;
use App\Models\CouponDetailHistory;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CouponDetailController extends Controller
{
    /**
     * 发券明细
     */
    public function manage(Request $request): JsonResponse
    {
        $sort = $request->input('sort', 'id');
        $order = $request->input('order', 'desc');
        $rows = $request->input('rows', 10);
        $filters = $request->input('filters', []);
        $query = CouponDetail::query()
            ->with(['customer:id,name,idcard', 'createUser:id,name'])
            ->select('coupon_details.*')
            ->leftJoin('customer', 'customer.id', '=', 'coupon_details.customer_id')
            ->when($request->input('created_at_start') && $request->input('created_at_end'), function (Builder $query) use ($request) {
                $query->whereBetween('coupon_details.created_at', [
                    Carbon::parse($request->input('created_at_start')),
                    Carbon::parse($request->input('created_at_end'))->endOfDay(),
                ]);
            })
            ->when($request->input('keyword'), function ($query) use ($request) {
                $query->where('customer.keyword', 'like', '%'.$request->input('keyword').'%');
            })
            ->queryConditions('CouponDetailIndex', $filters)
            ->orderBy($sort, $order)
            ->paginate($rows);

        $query->append(['status_text']);

        return response_success([
            'rows' => $query->items(),
            'total' => $query->total(),
        ]);
    }

    /**
     * 卡券余额变动历史
     */
    public function histories(Request $request): JsonResponse
    {
        $sort = $request->input('sort', 'created_at');
        $order = $request->input('order', 'desc');
        $rows = $request->input('rows', 10);
        $query = CouponDetailHistory::query()
            ->where('coupon_detail_id', $request->input('coupon_detail_id'))
            ->orderBy($sort, $order)
            ->paginate($rows);

        return response_success([
            'rows' => $query->items(),
            'total' => $query->total(),
        ]);
    }

    /**
     * 作废卡券，清空剩余券额并记录余额变动历史。
     *
     * @throws HisException
     */
    public function void(Request $request): JsonResponse
    {
        $id = $request->integer('id');
        if (! $id) {
            throw new HisException('请选择要作废的卡券!');
        }

        DB::beginTransaction();
        try {
            $couponDetail = CouponDetail::query()->lockForUpdate()->find($id);
            if (! $couponDetail) {
                throw new HisException('卡券记录不存在!');
            }

            if (! in_array($couponDetail->status, [CouponDetailStatus::UNUSED, CouponDetailStatus::PARTIALLY_USED], true)) {
                throw new HisException('当前状态的卡券无法作废!');
            }

            $before = $couponDetail->balance;
            $remark = $request->input('remark') ?: '作废卡券';

            CouponDetailHistory::query()->create([
                'coupon_id' => $couponDetail->coupon_id,
                'coupon_detail_id' => $couponDetail->id,
                'coupon_number' => $couponDetail->number,
                'customer_id' => $couponDetail->customer_id,
                'before' => $before,
                'amount' => -1 * abs($before),
                'after' => 0,
                'remark' => $remark,
                'historyable_type' => CouponDetail::class,
                'historyable_id' => $couponDetail->id,
            ]);

            $couponDetail->update([
                'status' => CouponDetailStatus::VOIDED,
                'balance' => 0,
            ]);

            DB::commit();

            return response_success();
        } catch (HisException $e) {
            DB::rollBack();
            throw $e;
        } catch (Exception $e) {
            DB::rollBack();
            throw new HisException($e->getMessage());
        }
    }
}
