<?php

namespace App\Http\Controllers\Web;

use Exception;
use Throwable;
use Carbon\Carbon;
use App\Models\Item;
use App\Models\Medium;
use App\Models\Customer;
use App\Models\Reception;
use App\Exceptions\HisException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\ReceptionRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Builder;

class ReceptionController extends Controller
{
    /**
     * 分诊记录
     * @param Request $request
     * @return JsonResponse
     */
    public function manage(Request $request): JsonResponse
    {
        $rows  = $request->input('rows', 10);
        $sort  = $request->input('sort', 'created_at');
        $order = $request->input('order', 'desc');
        $query = Reception::query()
            ->with([
                'user:id,name',
                'receptionItems',
                'medium:id,name',
                'department:id,name',
                'receptionType:id,name',
                'receptionUser:id,name',
            ])
            ->select([
                'reception.*',
                'customer.name'
            ])
            ->join('customer', 'customer.id', '=', 'reception.customer_id')
            ->when($type = $request->input('type'), fn(Builder $query) => $query->where('reception.type', $type))
            ->when($items = $request->input('items'), function (Builder $query) use ($items) {
                $query->leftJoin('reception_items', 'reception.id', '=', 'reception_items.reception_id')
                    ->whereIn('reception_items.item_id', Item::find($items)->getAllChild()->pluck('id'));
            })
            ->when($status = $request->input('status'), fn(Builder $query) => $query->where('reception.status', $status))
            ->when($doctor = $request->input('doctor'), fn(Builder $query) => $query->where('reception.doctor', $doctor))
            ->when($ek_user = $request->input('ek_user'), fn(Builder $query) => $query->where('reception.ek_user', $ek_user))
            ->when($user_id = $request->input('user_id'), fn(Builder $query) => $query->where('reception.user_id', $user_id))
            ->when($keyword = $request->input('keyword'), fn(Builder $query) => $query->whereLike('customer.keyword', "%{$keyword}%"))
            ->when($reception = $request->input('reception'), fn(Builder $query) => $query->where('reception.reception', $reception))
            ->when($medium_id = $request->input('medium_id'), fn(Builder $query) => $query->whereIn('reception.medium_id', Medium::find($medium_id)->getAllChild()->pluck('id')))
            ->when($consultant = $request->input('consultant'), fn(Builder $query) => $query->where('reception.consultant', $consultant))
            ->when($department_id = $request->input('department_id'), fn(Builder $query) => $query->where('reception.department_id', $department_id))
            ->when($request->input('created_at_start') && $request->input('created_at_end'), function (Builder $query) use ($request) {
                $query->whereBetween('reception.created_at', [
                    Carbon::parse($request->input('created_at_start')),
                    Carbon::parse($request->input('created_at_end'))->endOfDay()
                ]);
            })
            ->when(!user()->hasAnyAccess(['superuser', 'reception.view.all']), function (Builder $query) {
                $ids = user()->getReceptionViewUsersPermission();
                $query->where(function ($query) use ($ids) {
                    $query->whereIn('reception.user_id', $ids)->orWhere('reception.reception', $ids);
                });
            })
            ->orderBy($sort, $order)
            ->paginate($rows);

        return response_success([
            'rows'  => $query->items(),
            'total' => $query->total()
        ]);
    }

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
