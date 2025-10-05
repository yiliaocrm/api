<?php

namespace App\Http\Controllers\Web;

use Exception;
use Throwable;
use Carbon\Carbon;
use App\Models\Customer;
use App\Models\Consultant;
use App\Models\ReceptionOrder;
use App\Exceptions\HisException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\ConsultantRequest;
use App\Http\Requests\Consultant\CreateRequest;
use App\Http\Requests\Consultant\UpdateRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class ConsultantController extends Controller
{
    /**
     * 现场咨询列表
     * @param Request $request
     * @return JsonResponse
     */
    public function manage(Request $request): JsonResponse
    {
        $rows  = request('rows', 10);
        $query = Consultant::query()
            ->select([
                'reception.*',
                'customer.name'
            ])
            ->with([
                'failure:id,name',
                'orders' => function ($query) {
                    $query->with('units')->orderBy('created_at', 'desc');
                },
                'receptionItems:id,name'
            ])
            ->leftJoin('customer', 'customer.id', '=', 'reception.customer_id')
            ->when($request->input('customer_ascription'), function ($query) use ($request) {
                $query->where('customer.ascription', $request->input('customer_ascription'));
            })
            ->when($request->input('customer_keyword'), function ($query) use ($request) {
                $query->where('customer.keyword', 'like', '%' . $request->input('customer_keyword') . '%');
            })
            // 登记日期
            ->when($request->input('created_at_start') && $request->input('created_at_end'), function ($query) use ($request) {
                $query->whereBetween('reception.created_at', [
                    Carbon::parse($request->input('created_at_start')),
                    Carbon::parse($request->input('created_at_end'))->endOfDay()
                ]);
            })
            // 分诊状态
            ->when($request->input('type'), function ($query) use ($request) {
                $query->where('reception.type', $request->input('type'));
            })
            // 成交状态
            ->when($request->input('status'), function ($query) use ($request) {
                $query->where('reception.status', $request->input('status'));
            })
            // 咨询科室
            ->when($request->input('department_id'), function ($query) use ($request) {
                $query->where('reception.department_id', $request->input('department_id'));
            })
            // 现场咨询
            ->when($request->input('consultant'), function ($query) use ($request) {
                $query->where('reception.consultant', $request->input('consultant'));
            })
            // 接诊医生
            ->when($request->input('doctor'), function ($query) use ($request) {
                $query->where('reception.doctor', $request->input('doctor'));
            })
            // 二开人员
            ->when($request->input('ek_user'), function ($query) use ($request) {
                $query->where('reception.ek_user', $request->input('ek_user'));
            })
            // 查询权限
            ->when(!user()->hasAnyAccess(['superuser', 'consultant.view.all']), function ($query) {
                $users = user()->getConsultantViewUsersPermission();
                $query->where(function ($query) use ($users) {
                    $query->whereIn('reception.consultant', $users)->orWhereIn('reception.ek_user', $users);
                });
            })
            ->orderBy('reception.created_at', 'desc')
            ->paginate($rows);

        return response_success([
            'rows'  => $query->items(),
            'total' => $query->total()
        ]);
    }

    /**
     * 选择顾客后，填充数据
     * @param Request $request
     * @return JsonResponse
     */
    public function fill(Request $request): JsonResponse
    {
        $customer    = Customer::query()->find($request->input('customer_id'));
        $reception   = $customer->receptions()->orderBy('created_at', 'desc')->first();
        $reservation = $customer->reservations()->whereNull('cometime')->orderBy('created_at', 'desc')->first();
        $data        = [
            'type'          => 1,    // 接诊类型:初诊
            'medium_id'     => $customer->medium_id,
            'reception'     => user()->id,  // 接待人员
            'department_id' => user()->department_id
        ];

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
     * 查询信息
     * @param ConsultantRequest $request
     * @return JsonResponse
     */
    public function info(ConsultantRequest $request): JsonResponse
    {
        $consultant = Consultant::query()->find(
            $request->input('id')
        );
        $consultant->load([
            'orders' => function ($query) {
                $query->with('units')->orderBy('created_at', 'desc');
            },
            'failure:id,name',
        ]);
        return response_success($consultant);
    }

    /**
     * 录入现场咨询单
     * @param CreateRequest $request
     * @return JsonResponse
     * @throws HisException|Throwable
     */
    public function create(CreateRequest $request): JsonResponse
    {
        DB::beginTransaction();

        try {

            // 现场咨询
            $consultant = Consultant::query()->create(
                $request->formData()
            );

            // 现场开单
            $consultant->orders()->createMany(
                $request->orderData($consultant->customer_id)
            );

            // 创建[收费通知]
            if ($consultant->orders->isNotEmpty()) {
                $consultant->cashierable()->create([
                    'customer_id' => $consultant->customer_id,
                    'detail'      => $consultant->orders,
                    'payable'     => $consultant->orders->sum('payable'),
                    'income'      => 0,
                    'arrearage'   => 0,
                    'coupon'      => 0,
                    'status'      => 1,             // 未收费状态
                    'user_id'     => user()->id,    // 录单人员
                ]);
            }

            // 提交
            DB::commit();

            $consultant->load(['orders' => function ($query) {
                $query->with('units')->orderBy('created_at', 'desc');
            }]);

            return response_success($consultant);

        } catch (Exception $e) {
            DB::rollBack();
            throw new HisException($e->getMessage());
        }
    }

    /**
     * 更新现场咨询
     * @param UpdateRequest $request
     * @return JsonResponse
     * @throws HisException|Throwable
     */
    public function update(UpdateRequest $request): JsonResponse
    {
        DB::beginTransaction();

        try {

            $consultant = Consultant::query()->find(
                $request->input('id')
            );

            $deleted  = $request->deleteData($consultant);
            $inserted = $request->insertData($consultant->customer_id);
            $updated  = $request->updateData();
            $ids      = [];

            // 删除订单
            if (count($deleted)) {
                $consultant->orders()->whereIn('id', $deleted)->delete();
            }

            // 新增订单
            if (count($inserted)) {
                $ids = collect($consultant->orders()->createMany($inserted))->pluck('id')->toArray();
            }

            // 更新订单
            if (count($updated)) {
                foreach ($updated as $order) {
                    $ids[] = $order['id'];
                    ReceptionOrder::query()->find($order['id'])->update($order);
                }
            }

            // 创建收费通知单
            if (count($inserted) || count($updated)) {
                $detail = $consultant->orders()->whereIn('id', $ids)->get();
                $consultant->cashierable()->create([
                    'customer_id' => $consultant->customer_id,
                    'detail'      => $detail,
                    'payable'     => $detail->sum('payable'),
                    'income'      => 0,
                    'arrearage'   => 0,
                    'coupon'      => 0,
                    'status'      => 1,             // 未收费状态
                    'user_id'     => user()->id,    // 录单人员
                ]);
            }

            // 更新咨询信息
            $consultant->update(
                $request->formData()
            );

            DB::commit();

            // 加载关系
            $consultant->load(['orders' => function ($query) {
                $query->with('units')->orderBy('created_at', 'desc');
            }]);

            return response_success($consultant);

        } catch (Exception $e) {
            DB::rollBack();
            throw new HisException($e->getMessage());
        }
    }

    /**
     * 取消接待
     * @param ConsultantRequest $request
     * @return JsonResponse
     */
    public function cancel(ConsultantRequest $request): JsonResponse
    {
        $consultant = Consultant::query()->find(
            $request->input('id')
        );
        $consultant->update([
            'receptioned' => 0
        ]);
        return response_success($consultant);
    }
}
