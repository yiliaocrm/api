<?php

namespace App\Http\Controllers\Web;

use Exception;
use Throwable;
use Carbon\Carbon;
use App\Models\Treatment;
use App\Models\CustomerProduct;
use App\Exceptions\HisException;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Builder;
use App\Http\Requests\Web\TreatmentRequest;

class TreatmentController extends Controller
{
    /**
     * 顾客已购买项目
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $rows  = $request->input('rows', 100);
        $sort  = $request->input('sort', 'customer_product.created_at');
        $order = $request->input('order', 'desc');
        $query = CustomerProduct::query()
            ->select([
                'customer.name',
                'customer.sex',
                'customer.idcard',
                'customer_product.*'
            ])
            ->leftJoin('customer', 'customer.id', '=', 'customer_product.customer_id')
            ->when($request->input('created_at_start') && $request->input('created_at_end'), function ($query) use ($request) {
                $query->whereBetween('customer_product.created_at', [
                    Carbon::parse($request->input('created_at_start')),
                    Carbon::parse($request->input('created_at_end'))->endOfDay()
                ]);
            })
            ->when($request->input('keyword'), function ($query) use ($request) {
                $query->where('customer.keyword', 'like', '%' . $request->input('keyword') . '%');
            })
            ->when($request->input('product_name'), function ($query) use ($request) {
                $query->where('customer_product.product_name', 'like', '%' . $request->input('product_name') . '%');
            })
            ->when($request->input('package_name'), function ($query) use ($request) {
                $query->where('customer_product.package_name', 'like', '%' . $request->input('package_name') . '%');
            })
            ->when($request->input('status'), function ($query) use ($request) {
                $query->where('customer_product.status', $request->input('status'));
            })
            ->when($request->input('user_id'), function ($query) use ($request) {
                $query->where('customer_product.user_id', $request->input('user_id'));
            })
            ->when($request->input('deduct_department'), function ($query) use ($request) {
                $query->where('customer_product.deduct_department', $request->input('deduct_department'));
            })
            // 限制查询权限
            ->when(!user()->hasAnyAccess(['superuser', 'treatment.view.all']), function ($query) {
                $query->whereIn('customer_product.deduct_department', user()->getTreatmentViewDepartmentsPermission());
            })
            ->orderBy($sort, $order)
            ->paginate($rows);

        return response_success([
            'rows'  => $query->items(),
            'total' => $query->total()
        ]);
    }

    /**
     * 划扣记录
     * @param TreatmentRequest $request
     * @return JsonResponse
     */
    public function record(TreatmentRequest $request): JsonResponse
    {
        $sort    = $request->input('sort', 'treatment.created_at');
        $order   = $request->input('order', 'desc');
        $rows    = $request->input('rows', 10);
        $keyword = $request->input('keyword');
        $query   = Treatment::query()
            ->with([
                'user:id,name',
                'department:id,name',
                'treatmentParticipants.user:id,name',
                'product:id,type_id',
                'product.type:id,name',
            ])
            ->select([
                'treatment.*',
                'customer.name as customer_name',
                'customer.idcard as customer_idcard',
            ])
            ->leftJoin('customer', 'customer.id', '=', 'treatment.customer_id')
            ->queryConditions('TreatmentRecord')
            ->when($request->input('date.0') && $request->input('date.1'), function ($query) use ($request) {
                $query->whereBetween('treatment.created_at', [
                    Carbon::parse($request->input('date.0'))->startOfDay(),
                    Carbon::parse($request->input('date.1'))->endOfDay()
                ]);
            })
            ->when($keyword, fn(Builder $query) => $query->where('customer.keyword', 'like', '%' . $keyword . '%'))
            // 限制查询权限
            ->when(!user()->hasAnyAccess(['superuser', 'treatment.view.all']), function ($query) {
                $ids = user()->getTreatmentViewDepartmentsPermission();
                $query->where(function ($query) use ($ids) {
                    $query->whereIn('treatment.department_id', $ids);
                });
            })
            ->orderBy($sort, $order)
            ->paginate($rows);

        $query->append(['status_text']);

        return response_success([
            'rows'  => $query->items(),
            'total' => $query->total()
        ]);
    }

    /**
     * 创建划扣记录
     * @param TreatmentRequest $request
     * @return JsonResponse
     * @throws HisException|Throwable
     */
    public function create(TreatmentRequest $request): JsonResponse
    {
        DB::beginTransaction();
        try {

            // 被划扣项目信息
            $customerProduct = CustomerProduct::query()->find(
                $request->input('customer_product_id')
            );

            // 创建划扣记录
            $treatment = Treatment::query()->create(
                $request->formData($customerProduct)
            );

            // 创建配台记录
            $treatment->treatmentParticipants()->createMany(
                $request->participantsData()
            );

            // 更新顾客项目表
            $customerProduct->update([
                'leftover' => $customerProduct->leftover - $request->input('form.times'),
                'used'     => $customerProduct->used + $request->input('form.times')
            ]);

            // 写入提成
            $treatment->salesPerformance()->createMany(
                $request->salesPerformanceData($treatment, $customerProduct->reception_type, $customerProduct->cashier_id)
            );

            // 更新[顾客表]最后治疗时间
            $treatment->customer->update([
                'last_treatment' => now()
            ]);

            // 写入回访提醒
            DB::table('followup')->insert(
                $request->followupData($customerProduct->customer, $treatment->treatmentParticipants)
            );

            DB::commit();
            return response_success($customerProduct);

        } catch (Exception $e) {
            DB::rollBack();
            throw new HisException($e->getMessage());
        }
    }

    /**
     * 撤销划扣记录
     * @param TreatmentRequest $request
     * @return JsonResponse
     * @throws HisException|Throwable
     */
    public function undo(TreatmentRequest $request): JsonResponse
    {
        DB::beginTransaction();
        try {

            $treatment = Treatment::query()->find(
                $request->input('id')
            );

            $customerProduct = $treatment->customerProduct;

            // 更新状态
            $treatment->update(
                $request->formData()
            );

            // 更新顾客项目表
            $customerProduct->update([
                'leftover' => $customerProduct->leftover + $treatment->times,
                'used'     => $customerProduct->used - $treatment->times
            ]);

            // 撤回业绩
            $treatment->salesPerformance()->createMany(
                $request->salesPerformanceDataForUndo($customerProduct->cashier_id, $treatment, $customerProduct->reception_type)
            );

            // 更新[顾客表]最后一次治疗时间
            $treatment->customer->update(
                $request->lastTreatmentData($treatment)
            );

            // 附加顾客信息
            $treatment->customer_name   = $treatment->customer->name;
            $treatment->customer_idcard = $treatment->customer->idcard;

            DB::commit();
            return response_success($treatment);

        } catch (Exception $e) {
            DB::rollBack();
            throw new HisException($e->getMessage());
        }
    }

    /**
     * customer_product表对应的划扣记录
     * @param Request $request
     * @return JsonResponse
     */
    public function history(Request $request): JsonResponse
    {
        $data = Treatment::query()
            ->where('customer_product_id', $request->input('customer_product_id'))
            ->get();
        return response_success($data);
    }
}
