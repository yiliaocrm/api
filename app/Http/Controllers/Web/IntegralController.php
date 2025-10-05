<?php

namespace App\Http\Controllers\Web;

use Exception;
use Throwable;
use Carbon\Carbon;
use App\Models\Customer;
use App\Models\Integral;
use App\Exceptions\HisException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Builder;
use App\Http\Requests\Web\IntegralRequest;

class IntegralController extends Controller
{
    /**
     * 积分管理
     * @param IntegralRequest $request
     * @return JsonResponse
     */
    public function index(IntegralRequest $request): JsonResponse
    {
        $rows       = $request->input('rows', 10);
        $sort       = $request->input('sort', 'integral.id');
        $order      = $request->input('order', 'desc');
        $type       = $request->input('type');
        $expired    = $request->input('expired');
        $keyword    = $request->input('keyword');
        $created_at = $request->input('created_at');

        $query = Integral::query()
            ->select([
                'customer.name',
                'customer.idcard',
                'integral.id',
                'integral.customer_id',
                'integral.type',
                'integral.type_id',
                'integral.before',
                'integral.integral',
                'integral.after',
                'integral.expired',
                'integral.remark',
                'integral.created_at',
            ])
            ->leftJoin('customer', 'customer.id', '=', 'integral.customer_id')
            ->when($type, fn(Builder $query) => $query->whereIn('type', $type))
            ->when($keyword, fn(Builder $query) => $query->where('customer.keyword', 'like', '%' . $keyword . '%'))
            ->when($request->filled('expired'), fn(Builder $query) => $query->where('integral.expired', $expired))
            ->whereBetween('integral.created_at', [
                Carbon::parse($created_at[0])->startOfDay(),
                Carbon::parse($created_at[1])->endOfDay()
            ])
            ->orderBy($sort, $order)
            ->paginate($rows);

        return response_success([
            'rows'  => $query->items(),
            'total' => $query->total(),
        ]);
    }

    /**
     * 调整顾客积分
     * @param IntegralRequest $request
     * @return JsonResponse
     * @throws HisException|Throwable
     */
    public function adjust(IntegralRequest $request): JsonResponse
    {
        DB::beginTransaction();

        try {
            $customer = Customer::query()->find(
                $request->input('customer_id')
            );

            // 插入积分操作记录
            $customer->integrals()->create(
                $request->integralsData($customer)
            );

            // 更新顾客表
            $customer->update(
                $request->formData($customer)
            );

            DB::commit();

            return response_success($customer);
        } catch (Exception $e) {
            DB::rollBack();
            throw new HisException($e->getMessage());
        }
    }
}
