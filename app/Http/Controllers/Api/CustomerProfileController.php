<?php

namespace App\Http\Controllers\Api;

use App\Models\Customer;
use App\Models\Followup;
use App\Models\Reservation;
use App\Models\CustomerPhone;
use App\Models\CustomerPhoto;
use App\Models\CustomerPhoneView;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CustomerProfileRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Database\Eloquent\Builder;

class CustomerProfileController extends Controller
{
    /**
     * 加载顾客手机号
     * @param CustomerProfileRequest $request
     * @return JsonResponse
     */
    public function phone(CustomerProfileRequest $request): JsonResponse
    {
        $id    = $request->input('id');
        $show  = $request->input('show');
        $query = CustomerPhone::query()
            ->with([
                'relationship:id,name'
            ])
            ->where('customer_id', $request->input('customer_id'));

        if (!user()->hasAnyAccess(['superuser', 'app.customer.phone']) && $show) {
            return response_error(msg: '没有权限查看顾客手机号码');
        }

        // 显示原始手机号码
        if ($show) {
            CustomerPhone::$showOriginalPhone = true;
        }

        // 查询顾客单个号码或全部号码
        if ($id) {
            $data = $query->where('id', $id)->first();
        } else {
            $data = $query->get();
        }

        // 记录查看日志
        if ($show && $id) {
            CustomerPhoneView::query()->create([
                'phone'       => $data->phone,
                'user_id'     => user()->id,
                'customer_id' => $request->input('customer_id'),
            ]);
        }

        return response_success($data);
    }

    /**
     * 用户画像
     * @param CustomerProfileRequest $request
     * @return JsonResponse
     */
    public function profile(CustomerProfileRequest $request): JsonResponse
    {
        $customer = Customer::query()->find(
            $request->input('customer_id')
        );

        $customer->load([
            'tags',
            'consultantUser:id,name',
            'ascriptionUser:id,name',
        ]);

        return response_success([
            'customer_id'  => $customer->id,
            'tags'         => $customer->tags,
            'assets'       => [
                'total_payment' => $customer->total_payment,
                'balance'       => $customer->balance,
                'amount'        => $customer->amount,
            ],
            'affiliations' => [
                'consultant' => $customer->consultantUser,
                'ascription' => $customer->ascriptionUser,
            ],
        ]);
    }

    /**
     * 顾客概览
     * @param CustomerProfileRequest $request
     * @return JsonResponse
     */
    public function overview(CustomerProfileRequest $request): JsonResponse
    {
        $customer = Customer::query()
            ->select([
                'id',
                'sex',
                'name',
                'idcard',
            ])
            ->with([
                'phone'
            ])
            ->find($request->input('customer_id'));
        return response_success($customer);
    }

    /**
     * 顾客照片
     * @param CustomerProfileRequest $request
     * @return JsonResponse
     */
    public function photo(CustomerProfileRequest $request): JsonResponse
    {
        $rows  = $request->input('rows', 10);
        $query = CustomerPhoto::query()
            ->with([
                'details' => function ($query) {
                    $query->orderBy('created_at', 'asc');
                }
            ])
            ->where('customer_id', $request->input('customer_id'))
            ->orderBy('created_at', 'desc')
            ->paginate($rows);

        return response_success([
            'rows'  => $query->items(),
            'total' => $query->total()
        ]);
    }


    /**
     * 查询顾客回访记录
     * @param CustomerProfileRequest $request
     * @return JsonResponse
     */
    public function followup(CustomerProfileRequest $request): JsonResponse
    {
        $rows  = $request->input('rows', 10);
        $sort  = $request->input('sort', 'created_at');
        $order = $request->input('order', 'desc');
        $query = Followup::query()
            ->with([
                'followupType:id,name',
                'followupTool:id,name',
                'followupUserInfo:id,name',
            ])
            ->where('customer_id', $request->input('customer_id'))
            ->when($request->input('status'), fn(Builder $query) => $query->where('status', $request->input('status')))
            ->orderBy($sort, $order)
            ->paginate($rows);

        return response_success([
            'rows'  => $query->items(),
            'total' => $query->total()
        ]);
    }

    /**
     * 报单记录
     * @param CustomerProfileRequest $request
     * @return JsonResponse
     */
    public function reservation(CustomerProfileRequest $request): JsonResponse
    {
        $rows  = $request->input('rows', 10);
        $sort  = $request->input('sort', 'created_at');
        $order = $request->input('order', 'desc');
        $query = Reservation::query()
            ->where('customer_id', $request->input('customer_id'))
            ->orderBy($sort, $order)
            ->paginate($rows);

        return response_success([
            'rows'  => $query->items(),
            'total' => $query->total()
        ]);
    }
}
