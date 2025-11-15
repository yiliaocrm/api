<?php

namespace App\Http\Controllers\Web;

use Exception;
use Throwable;
use App\Models\Customer;
use App\Models\Reception;
use App\Exceptions\HisException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\ReceptionRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class ReceptionController extends Controller
{
    /**
     * 自动填充分诊信息
     * 1、读取最后一条未上门的网电记录
     * 2、读取上次分诊记录
     * 3、读取顾客基本信息
     * @param ReceptionRequest $request
     * @return JsonResponse
     */
    public function fill(ReceptionRequest $request): JsonResponse
    {
        $customer = Customer::query()
            ->with([
                'consultantUser:id,name',
                'ascriptionUser:id,name',
            ])
            ->find($request->input('customer_id'));

        // 创建一个未保存的Reception实例
        $reception = new Reception();

        // 默认值
        $reception->type       = 1; // 默认为初诊
        $reception->medium_id  = $customer->medium_id;
        $reception->reception  = user()->id; // 接待人员为当前用户
        $reception->consultant = $customer->consultant; // 归属咨询

        // 尝试从最新的未上门预约记录中获取信息
        $reservation = $customer->reservations()->whereNull('cometime')->latest()->first();
        if ($reservation) {
            $reception->department_id = $reservation->department_id;
            $reception->medium_id     = $reservation->medium_id;
            $reception->items         = $reservation->items;
        }

        // 尝试从最后一次分诊记录中获取信息（会覆盖预约记录的信息）
        $lastReception = $customer->receptions()->latest()->first();
        if ($lastReception) {
            $reception->department_id = $lastReception->department_id;
            $reception->medium_id     = $lastReception->medium_id;
            $reception->type          = 2; // 有历史分诊记录，默认为复诊

            // 如果最后一次分诊是今天，则沿用其接诊类型
            if ($lastReception->created_at->isToday()) {
                $reception->type = $lastReception->type;
            }
        }

        // 附加顾客信息
        $reception->customer = $customer;

        return response_success($reception);
    }

    /**
     * 查看接待信息
     * @param ReceptionRequest $request
     * @return JsonResponse
     */
    public function info(ReceptionRequest $request): JsonResponse
    {
        $reception = Reception::query()->find(
            $request->input('id')
        );
        $reception->load([
            'customer:id,name,sex,idcard,ascription,consultant',
            'customer.consultantUser:id,name',
            'customer.ascriptionUser:id,name',
        ]);
        return response_success($reception);
    }

    /**
     * 创建分诊
     * @param ReceptionRequest $request
     * @return JsonResponse
     * @throws HisException|Throwable
     */
    public function create(ReceptionRequest $request): JsonResponse
    {
        DB::beginTransaction();
        try {
            $reception = Reception::query()->create(
                $request->formData()
            );
            DB::commit();
            return response_success($reception);
        } catch (Exception $e) {
            DB::rollBack();
            throw new HisException($e->getMessage(), $e->getCode());
        }
    }

    /**
     * 更新分诊记录
     * @param ReceptionRequest $request
     * @return JsonResponse
     * @throws HisException|Throwable
     */
    public function update(ReceptionRequest $request): JsonResponse
    {
        DB::beginTransaction();
        try {
            $reception = Reception::query()->find(
                $request->input('id')
            );
            $reception->update(
                $request->formData()
            );
            DB::commit();
            return response_success($reception);
        } catch (Exception $e) {
            DB::rollBack();
            throw new HisException($e->getMessage(), $e->getCode());
        }
    }

    /**
     * 删除分诊
     * @param ReceptionRequest $request
     * @return JsonResponse
     * @throws HisException|Throwable
     */
    public function remove(ReceptionRequest $request): JsonResponse
    {
        DB::transaction(function () use ($request) {
            Reception::query()->find($request->input('id'))->delete();
        });
        return response_success(msg: '分诊记录删除成功');
    }

    /**
     * 改派现场咨询
     * @param ReceptionRequest $request
     * @return JsonResponse
     */
    public function dispatchConsultant(ReceptionRequest $request): JsonResponse
    {
        $reception = Reception::query()->find(
            $request->input('id')
        );
        $reception->update([
            'consultant' => $request->input('new')
        ]);
        return response_success($reception);
    }

    /**
     * 改派助诊医生
     * @param ReceptionRequest $request
     * @return JsonResponse
     */
    public function dispatchDoctor(ReceptionRequest $request): JsonResponse
    {
        $reception = Reception::query()->find(
            $request->input('id')
        );
        $reception->update([
            'doctor' => $request->input('new')
        ]);
        return response_success($reception);
    }
}
