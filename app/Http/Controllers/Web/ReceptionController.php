<?php

namespace App\Http\Controllers\Web;

use Exception;
use Throwable;
use Carbon\Carbon;
use App\Models\Customer;
use App\Models\Reception;
use App\Exceptions\HisException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\ReceptionRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class ReceptionController extends Controller
{
    /**
     * 自动填充分诊信息
     * 1、读取最后一条未上门的网电记录
     * 2、读取上次分诊记录
     * 3、读取顾客基本信息
     * @param Request $request
     * @return JsonResponse
     */
    public function fill(Request $request): JsonResponse
    {
        $data        = [];
        $customer    = Customer::query()->find($request->input('customer_id'));
        $reception   = $customer->receptions()->orderBy('created_at', 'desc')->first();
        $reservation = $customer->reservations()->whereNull('cometime')->orderBy('created_at', 'desc')->first();

        $data['consultant'] = $customer->consultant;
        $data['type']       = 1; // 接诊类型:初诊
        $data['medium_id']  = $customer->medium_id;
        $data['reception']  = user()->id;   // 接待人员

        if ($reservation) {
            $data['department_id'] = $reservation->department_id;
            $data['medium_id']     = $reservation->medium_id;
            $data['items']         = $reservation->items;
        }

        if ($reception) {
            $data['department_id'] = $reception->department_id;
            $data['type']          = 2;
            $data['medium_id']     = $reception->medium_id;

            // 最后一次[分诊记录]是今天
            if ($reception->created_at->startOfDay()->diffInDays(Carbon::now()->startOfDay()) == 0) {
                $data['type'] = $reception->type;
            }
        }

        return response_success($data);
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
