<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Erkai\CreateRequest;
use App\Http\Requests\Erkai\InfoRequest;
use App\Http\Requests\Erkai\UpdateRequest;
use App\Models\Erkai;
use Exception;
use Throwable;
use Carbon\Carbon;
use App\Exceptions\HisException;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Builder;

class ErkaiController extends Controller
{
    public function manage(Request $request): JsonResponse
    {
        $data  = [];
        $rows  = $request->input('rows', 10);
        $query = Erkai::query()
            ->with(['customer:id,idcard,name', 'details.units'])
            ->select('erkai.*')
            ->leftJoin('customer', 'customer.id', '=', 'erkai.customer_id')
            ->when($request->input('created_at_start') && $request->input('created_at_end'), function (Builder $query) use ($request) {
                $query->whereBetween('erkai.created_at', [
                    Carbon::parse($request->input('created_at_start')),
                    Carbon::parse($request->input('created_at_end'))->endOfDay()
                ]);
            })
            // 顾客信息
            ->when($request->input('keyword'), function (Builder $query) use ($request) {
                $query->where('customer.keyword', 'like', '%' . $request->input('keyword') . '%');
            })
            // 录单人员
            ->when($request->input('user_id'), function (Builder $query) use ($request) {
                $query->where('erkai.user_id', $request->input('user_id'));
            })
            // 开单科室
            ->when($request->input('department_id'), function (Builder $query) use ($request) {
                $query->where('erkai.department_id', $request->input('department_id'));
            })
            // 成交状态
            ->when($request->input('status'), function (Builder $query) use ($request) {
                $query->where('erkai.status', $request->input('status'));
            })
            ->orderBy('created_at', 'desc')
            ->paginate($rows);

        return response_success([
            'rows'  => $query->items(),
            'total' => $query->total()
        ]);
    }

    /**
     * 二开信息
     * @param InfoRequest $request
     * @return JsonResponse
     */
    public function info(InfoRequest $request): JsonResponse
    {
        $data = Erkai::query()->find(
            $request->input('id')
        );
        $data->load(['details']);
        return response_success($data);
    }

    /**
     * 创建二开记录
     * @param CreateRequest $request
     * @return JsonResponse
     * @throws HisException|Throwable
     */
    public function create(CreateRequest $request): JsonResponse
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
            $erkai->load(['customer:id,idcard,name', 'details']);

            DB::commit();
            return response_success($erkai);
        } catch (Exception $e) {
            DB::rollBack();
            throw new HisException($e->getMessage());
        }
    }

    /**
     * 更新二开记录
     * @param UpdateRequest $request
     * @return JsonResponse
     * @throws HisException|Throwable
     */
    public function update(UpdateRequest $request): JsonResponse
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
            $erkai->load(['customer:id,idcard,name', 'details']);

            DB::commit();
            return response_success($erkai);

        } catch (Exception $e) {
            DB::rollBack();
            throw new HisException($e->getMessage());
        }
    }
}
