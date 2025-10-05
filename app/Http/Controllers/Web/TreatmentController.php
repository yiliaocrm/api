<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Treatment\UndoRequest;
use App\Http\Requests\Treatment\CreateRequest;
use Exception;
use Throwable;
use Carbon\Carbon;
use App\Exceptions\HisException;
use App\Models\Treatment;
use App\Models\ProductType;
use App\Models\CustomerProduct;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

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
     * @param Request $request
     * @return JsonResponse
     */
    public function record(Request $request): JsonResponse
    {
        $sort  = $request->input('sort', 'treatment.created_at');
        $order = $request->input('order', 'desc');
        $rows  = $request->input('rows', 10);
        $query = Treatment::query()
            ->select([
                'treatment.*',
                'customer.name as customer_name',
                'customer.idcard as customer_idcard',
                'product_type.name as product_type_name',
            ])
            ->leftJoin('customer', 'customer.id', '=', 'treatment.customer_id')
            ->leftJoin('product', 'treatment.product_id', '=', 'product.id')
            ->leftJoin('product_type', 'product.type_id', '=', 'product_type.id')
            ->when($request->input('keyword'), fn($query) => $query->where('customer.keyword', 'like', '%' . $request->input('keyword') . '%'))
            ->when($request->input('product_name'), fn($query) => $query->where('treatment.product_name', 'like', '%' . $request->input('product_name') . '%'))
            ->when($request->input('remark'), fn($query) => $query->where('treatment.remark', 'like', '%' . $request->input('remark') . '%'))
            ->when($request->input('user_id'), fn($query) => $query->where('treatment.user_id', $request->input('user_id')))
            ->when($request->input('department_id'), fn($query) => $query->where('treatment.department_id', $request->input('department_id')))
            ->when($request->input('package_name'), fn($query) => $query->where('treatment.package_name', 'like', '%' . $request->input('package_name') . '%'))
            ->when($request->input('created_at_start') && $request->input('created_at_end'), function ($query) use ($request) {
                $query->whereBetween('treatment.created_at', [
                    Carbon::parse($request->input('created_at_start')),
                    Carbon::parse($request->input('created_at_end'))->endOfDay()
                ]);
            })
            ->when($request->input('participants'), function ($query) use ($request) {
                $query->leftJoin('treatment_participants', 'treatment.id', '=', 'treatment_participants.treatment_id')
                    ->where('treatment_participants.user_id', $request->input('participants'));
            })
            ->when(request('product_type') && request('product_type') != 1, function ($query) {
                $query->whereIn('product.type_id', ProductType::find(request('product_type'))->getAllChild()->pluck('id'));
            })
            // 限制查询权限
            ->when(!user()->hasAnyAccess(['superuser', 'treatment.view.all']), function ($query) {
                $departments = user()->getTreatmentViewDepartmentsPermission();

                if (count($departments) > 1) {
                    $query->where(function ($query) use ($departments) {
                        $query->whereIn('treatment.department_id', $departments);
                    });
                } else {
                    $query->where(function ($query) {
                        $query->where('treatment.department_id', user()->department_id);
                    });
                }
            })
            ->orderBy($sort, $order)
            ->paginate($rows);

        return response_success([
            'rows'  => $query->items(),
            'total' => $query->total()
        ]);
    }

    /**
     * 创建划扣记录
     * @param CreateRequest $request
     * @return JsonResponse
     * @throws HisException|Throwable
     */
    public function create(CreateRequest $request): JsonResponse
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
     * @param UndoRequest $request
     * @return JsonResponse
     * @throws HisException|Throwable
     */
    public function undo(UndoRequest $request): JsonResponse
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
                $request->salesPerformanceData($customerProduct->cashier_id, $treatment, $customerProduct->reception_type)
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
