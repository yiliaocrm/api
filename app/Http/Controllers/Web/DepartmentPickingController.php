<?php

namespace App\Http\Controllers\Web;

use Exception;
use Throwable;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Exceptions\HisException;
use App\Models\DepartmentPicking;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Builder;
use App\Http\Requests\DepartmentPicking\CheckRequest;
use App\Http\Requests\DepartmentPicking\CreateRequest;
use App\Http\Requests\DepartmentPicking\RemoveRequest;
use App\Http\Requests\DepartmentPicking\UpdateRequest;

class DepartmentPickingController extends Controller
{
    public function manage(Request $request): JsonResponse
    {
        $sort    = $request->input('sort', 'id');
        $order   = $request->input('order', 'desc');
        $rows    = $request->input('rows', 10);
        $keyword = $request->input('keyword');
        $query   = DepartmentPicking::query()
            ->with([
                'type:id,name',
                'warehouse:id,name',
                'department:id,name',
                'details.goodsUnits',
                'details.inventoryBatchs',
                'user:id,name',
                'auditor:id,name',
                'createUser:id,name'
            ])
            ->select([
                'department_picking.*'
            ])
            ->leftJoin('department_picking_detail', 'department_picking.id', '=', 'department_picking_detail.department_picking_id')
            ->when($keyword, fn(Builder $query) => $query->where('department_picking_detail.goods_name', 'like', "%{$keyword}%"))
            ->queryConditions('DepartmentPickingIndex')
            ->groupBy('department_picking.id')
            ->orderBy("department_picking.{$sort}", $order)
            ->paginate($rows);

        return response_success([
            'rows'  => $query->items(),
            'total' => $query->total()
        ]);
    }

    /**
     * 科室领料登记
     * @param CreateRequest $request
     * @return JsonResponse
     * @throws HisException|Throwable
     */
    public function create(CreateRequest $request): JsonResponse
    {
        DB::beginTransaction();
        try {

            // 主表
            $picking = DepartmentPicking::query()->create(
                $request->formData()
            );

            // 明细表
            $picking->details()->createMany(
                $request->detailData($picking)
            );

            DB::commit();
            return response_success($picking);

        } catch (Exception $e) {
            DB::rollBack();
            throw new HisException($e->getMessage());
        }
    }

    /**
     * 更新科室领料单
     * @param UpdateRequest $request
     * @return JsonResponse
     * @throws HisException|Throwable
     */
    public function update(UpdateRequest $request): JsonResponse
    {
        DB::beginTransaction();
        try {
            $picking = DepartmentPicking::query()->find(
                $request->input('id')
            );

            $picking->update(
                $request->formData()
            );

            // 删除明细
            $picking->details()->delete();

            // 新增明细
            $picking->details()->createMany(
                $request->detailData($picking)
            );

            DB::commit();
            return response_success($picking);

        } catch (Exception $e) {
            DB::rollBack();
            throw new HisException($e->getMessage());
        }
    }

    /**
     * 审核单据
     * @param CheckRequest $request
     * @return JsonResponse
     * @throws HisException|Throwable
     */
    public function check(CheckRequest $request): JsonResponse
    {
        DB::beginTransaction();
        try {

            $picking = DepartmentPicking::query()
                ->lockForUpdate()
                ->find($request->input('id'));

            // 更新主表
            $picking->update([
                'status'     => 2,
                'check_user' => user()->id,
                'check_time' => Carbon::now()
            ]);

            // 更新明细表状态
            $picking->details()->update([
                'status' => 2
            ]);

            // 更新库存批次数量
            $picking->details->each(function ($detail) use ($request) {
                $detail->inventoryBatch->update(
                    $request->transformers($detail)
                );
            });

            // 更新库存变动明细表
            $picking->inventoryDetail()->createMany(
                $request->inventoryDetailData($picking)
            );

            DB::commit();
            return response_success($picking);
        } catch (Exception $e) {
            DB::rollBack();
            throw new HisException($e->getMessage());
        }
    }

    /**
     * 删除科室领料(草稿)
     * @param RemoveRequest $request
     * @return JsonResponse
     */
    public function remove(RemoveRequest $request): JsonResponse
    {
        $picking = DepartmentPicking::query()->find($request->input('id'));
        $picking->details()->delete();
        $picking->delete();
        return response_success();
    }
}
