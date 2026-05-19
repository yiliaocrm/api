<?php

namespace App\Http\Controllers\Web;

use App\Exceptions\HisException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Erkai\ErkaiRequest;
use App\Models\Customer;
use App\Models\Erkai;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class ErkaiController extends Controller
{
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

    public function manage(ErkaiRequest $request): JsonResponse
    {
        $rows = $request->input('rows', 10);
        $sort = $request->input('sort', 'created_at');
        $order = $request->input('order', 'desc');
        $keyword = $request->input('keyword');
        $date = $request->input('date');
        $filters = $request->input('filters', []);

        $builder = Erkai::query()
            ->with(['customer:id,idcard,name,keyword', 'department:id,name', 'user:id,name'])
            ->select('erkai.*')
            ->leftJoin('customer', 'customer.id', '=', 'erkai.customer_id')
            ->whereBetween('erkai.created_at', [
                Carbon::parse($date[0])->startOfDay(),
                Carbon::parse($date[1])->endOfDay(),
            ])
            ->when($keyword, fn (Builder $builder) => $builder->whereLike('customer.keyword', '%'.$keyword.'%'))
            ->queryConditions('ErkaiIndex', $filters)
            ->orderBy("erkai.{$sort}", $order);

        $query = $builder->clone()->paginate($rows);

        $query->append(['status_text']);

        $footer = [
            [
                'customer.name' => '页小计:',
                'payable' => collect($query->items())->sum('payable'),
                'income' => collect($query->items())->sum('income'),
                'deposit' => collect($query->items())->sum('deposit'),
                'coupon' => collect($query->items())->sum('coupon'),
                'arrearage' => collect($query->items())->sum('arrearage'),
            ],
            [
                'customer.name' => '总合计:',
                'payable' => floatval($builder->clone()->sum('erkai.payable')),
                'income' => floatval($builder->clone()->sum('erkai.income')),
                'deposit' => floatval($builder->clone()->sum('erkai.deposit')),
                'coupon' => floatval($builder->clone()->sum('erkai.coupon')),
                'arrearage' => floatval($builder->clone()->sum('erkai.arrearage')),
            ],
        ];

        return response_success([
            'rows' => $query->items(),
            'total' => $query->total(),
            'footer' => $footer,
        ]);
    }

    /**
     * 二开信息
     */
    public function info(ErkaiRequest $request): JsonResponse
    {
        $data = Erkai::query()
            ->with([
                'customer:id,idcard,name,keyword',
                'details.units',
            ])
            ->find($request->input('id'));

        $data?->append('status_text');

        return response_success($data);
    }

    /**
     * 创建二开记录
     *
     * @throws HisException|Throwable
     */
    public function create(ErkaiRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            // 主单
            $erkai = Erkai::query()->create(
                $request->formData()
            );

            // 明细
            $erkai->details()->createMany(
                $request->detailData($erkai)
            );

            // 收费通知
            $erkai->cashierable()->create(
                $request->cashierData($erkai)
            );

            // 加载关系
            $erkai->load(['customer:id,idcard,name,keyword', 'details.units']);
            $erkai->append('status_text');

            DB::commit();

            return response_success($erkai);
        } catch (Exception $e) {
            DB::rollBack();
            throw new HisException($e->getMessage());
        }
    }

    /**
     * 更新二开记录
     *
     * @throws HisException|Throwable
     */
    public function update(ErkaiRequest $request): JsonResponse
    {
        DB::beginTransaction();
        try {

            // 主单信息
            $erkai = Erkai::query()->find(
                $request->input('id')
            );

            // 更新主单
            $erkai->update(
                $request->formData()
            );

            // 删掉明细表
            $erkai->details()->delete();

            // 重新添加
            $erkai->details()->createMany(
                $request->detailData($erkai)
            );

            // 收费通知
            $erkai->cashierable()->create(
                $request->cashierData($erkai)
            );

            // 加载关系
            $erkai->load(['customer:id,idcard,name,keyword', 'details.units']);
            $erkai->append('status_text');

            DB::commit();

            return response_success($erkai);

        } catch (Exception $e) {
            DB::rollBack();
            throw new HisException($e->getMessage());
        }
    }
}
