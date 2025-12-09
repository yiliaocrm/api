<?php

namespace App\Http\Controllers\Web;

use App\Exceptions\HisException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\OutpatientRequest;
use App\Models\Customer;
use App\Models\Outpatient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class OutpatientController extends Controller
{
    /**
     * 门诊记录列表
     * @param Request $request
     * @return JsonResponse
     */
    public function manage(request $request): JsonResponse
    {
        $rows  = $request->input('rows', 10);
        $sort  = $request->input('sort', 'created_at');
        $order = $request->input('order', 'desc');
        $query = Outpatient::query()
            ->with([
                'customer:id,name,idcard,sex,age',
                'emr',
                'prescriptions.details'
            ])
            ->where('doctor', user()->id)
            ->orderBy('receptioned', 'asc')
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
     * 填充
     * @param Request $request
     * @return JsonResponse
     */
    public function fill(Request $request)
    {
        $data = [
            'type'          => 1,
            'doctor'        => user()->id,
            'department_id' => user()->department_id,
            'medium_id'     => Customer::query()->find($request->input('customer_id'))->medium_id,
        ];
        return response_success($data);
    }

    /**
     * 新增
     * @param OutpatientRequest $request
     * @return JsonResponse
     * @throws HisException|Throwable
     */
    public function create(OutpatientRequest $request)
    {
        DB::beginTransaction();
        try {
            // 门诊记录
            $outpatient = Outpatient::query()->create(
                $request->formData()
            );

            // 电子病历
            $outpatient->emr()->create(
                $request->emrData()
            );

            // 西药处方、和处方明细
            $prescriptions = $request->prescriptionsData([
                'reception_id'  => $outpatient->id,
                'emr_id'        => $outpatient->emr->id,
                'customer_id'   => $outpatient->customer_id,
                'department_id' => $outpatient->department_id,
                'doctor_id'     => $outpatient->doctor,
                'diagnosis'     => $outpatient->emr->diagnosis,
            ]);

            foreach ($prescriptions as $k) {
                $prescription = $outpatient->prescriptions()->create(
                    $k['prescription']
                );
                $prescription->details()->createMany(
                    $k['detail']
                );
            }

            // 开单了,创建[收费通知单] ps:目前只有处方
            if ($outpatient->prescriptionDetails->count()) {
                $outpatient->cashierable()->create([
                    'customer_id' => $outpatient->customer_id,
                    'status'      => 1,                                                             // 未收费
                    'payable'     => collect($prescriptions)->pluck('prescription')->sum('amount'), // 应付金额
                    'income'      => 0,                                                             // 实收金额(不包含余额支付)
                    'deposit'     => 0,                                                             // 余额支付
                    'arrearage'   => 0,                                                             // 本单欠款金额
                    'user_id'     => user()->id,
                    'detail'      => [
                        'prescriptions' => Outpatient::with('prescriptions.details')->find($outpatient->id)->prescriptions
                    ]
                ]);
            }

//            DB::commit();

            $data = Outpatient::with(['customer', 'emr', 'prescriptions.details'])->find($outpatient->id);
            return response_success($data);

        } catch (\Exception $e) {
            DB::rollBack();
            throw new HisException($e->getMessage());
        }
    }

    /**
     * 更新门诊记录
     * @param OutpatientRequest $request
     * @return JsonResponse
     * @throws HisException|Throwable
     */
    public function update(OutpatientRequest $request)
    {
        DB::beginTransaction();
        try {

            $outpatient = Outpatient::query()->find(
                $request->input('id')
            );

            // 更新就诊信息
            $outpatient->update(
                $request->formData()
            );

            // 更新电子病历
            $outpatient->emr()->updateOrCreate(
                ['reception_id' => $outpatient->id],
                $request->emrData($outpatient->customer_id)
            );


            // 更新处方
            $prescriptions = $request->prescriptionsData([
                'emr_id'        => $outpatient->emr->id,
                'customer_id'   => $outpatient->customer_id,
                'department_id' => $outpatient->department_id,
                'doctor_id'     => $outpatient->doctor,
                'diagnosis'     => $outpatient->emr->diagnosis,
            ]);

            $outpatient->prescriptions()->saveMany($prescriptions);

            // 开单了,创建[收费通知单] ps:目前只有处方
            if (count($prescriptions)) {
                $outpatient->cashierable()->create([
                    'customer_id' => $outpatient->customer_id,
                    'status'      => 1,                                       // 未收费
                    'payable'     => collect($prescriptions)->sum('amount'),  // 应付金额
                    'income'      => 0,                                       // 实收金额(不包含余额支付)
                    'deposit'     => 0,                                       // 余额支付
                    'arrearage'   => 0,                                       // 本单欠款金额
                    'user_id'     => user()->id,
                    'detail'      => [
                        'prescriptions' => $outpatient->prescriptions
                    ]
                ]);
            }

            // 提交事务
//            DB::commit();

            $data = Outpatient::with(['customer', 'emr', 'prescriptions'])->find($request->input('id'));
            return response_success($data);

        } catch (\Exception $e) {
            DB::rollBack();
            throw new HisException($e->getMessage());
        }

    }
}
