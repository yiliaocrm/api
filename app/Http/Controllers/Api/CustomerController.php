<?php

namespace App\Http\Controllers\Api;

use App\Models\Customer;
use App\Models\CustomerPhone;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CustomerRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Database\Eloquent\Builder;

class CustomerController extends Controller
{
    /**
     * 顾客列表
     * @param CustomerRequest $request
     * @return JsonResponse
     */
    public function index(CustomerRequest $request): JsonResponse
    {
        $rows  = $request->input('rows', 10);
        $sort  = $request->input('sort', 'created_at');
        $order = $request->input('order', 'desc');
        $query = Customer::query()
            ->with([
                'tags',
                'createUser:id,name',
                'ascriptionUser:id,name',
            ])
            ->select([
                'customer.id',
                'customer.sex',
                'customer.name',
                'customer.idcard',
                'customer.user_id',
                'customer.medium_id',
                'customer.ascription',
                'customer.consultant',
                'customer.first_time',
            ])
            // 权限限制
            ->when(!user()->hasAnyAccess(['superuser', 'customer.view.all']), function (Builder $query) {
                $ids = user()->getCustomerViewUsersPermission();
                $query->where(function ($query) use ($ids) {
                    $query->whereIn('ascription', $ids)->orWhereIn('consultant', $ids);
                });
            })
            ->orderBy($sort, $order)
            ->paginate($rows);

        $query->getCollection()->each(function ($customer) {
            $customer->items_name  = is_array($customer->items) ? get_items_name($customer->items) : '';
            $customer->medium_name = get_medium_name($customer->medium_id, true);
        });

        return response_success([
            'rows'  => $query->items(),
            'total' => $query->total()
        ]);
    }

    /**
     * 通过[关键词]或[电话]搜索顾客
     * @param CustomerRequest $request
     * @return JsonResponse
     */
    public function query(CustomerRequest $request): JsonResponse
    {
        $rows     = $request->input('rows', 10);
        $keyword  = $request->input('keyword');
        $category = $request->input('category', 'keyword');
        $query    = Customer::query()
            ->select([
                'id',
                'idcard',
                'name',
                'sex',
                'consultant',
                'ascription',
                'medium_id',
                'first_time'
            ])
            ->with([
                'createUser:id,name',
                'consultantUser:id,name',
                'ascriptionUser:id,name',
            ])
            // 关键词查询
            ->when($category == 'keyword', fn(Builder $query) => $query->whereLike('keyword', "%{$keyword}%"))
            // 输入电话查询
            ->when($category == 'phone', function (Builder $query) use ($keyword) {
                $phone = CustomerPhone::query()->where('phone', $keyword)->first();
                if ($phone) {
                    $query->where('id', $phone->customer_id);
                } else {
                    $query->where('id', null);
                }
            })
            // 权限限制
            ->when($category == 'keyword' && !user()->hasAnyAccess(['superuser', 'customer.view.all']), function (Builder $query) {
                $ids = user()->getCustomerViewUsersPermission();
                $query->where(function ($query) use ($ids) {
                    $query->whereIn('ascription', $ids)->orWhereIn('consultant', $ids);
                });
            })
            ->orderBy('created_at', 'desc')
            ->paginate($rows);

        $query->getCollection()->each(function ($customer) {
            $customer->medium_name = get_medium_name($customer->medium_id, true);
        });

        return response_success([
            'rows'  => $query->items(),
            'total' => $query->total()
        ]);
    }

    /**
     * 加载顾客信息
     * @param CustomerRequest $request
     * @return JsonResponse
     */
    public function info(CustomerRequest $request): JsonResponse
    {
        $customer = Customer::query()->find(
            $request->input('id')
        );

        $customer->setAttribute('medium_name', get_medium_name($customer->medium_id, true));

        $customer->load([
            'consultantUser:id,name',
            'ascriptionUser:id,name',
        ]);

        return response_success($customer);
    }

    /**
     * 客户建档
     * @param CustomerRequest $request
     * @return JsonResponse
     */
    public function create(CustomerRequest $request): JsonResponse
    {
        $customer = Customer::query()->create(
            $request->createData()
        );
        return response_success($customer);
    }
}
