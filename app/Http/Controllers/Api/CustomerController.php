<?php

namespace App\Http\Controllers\Api;

use App\Models\Customer;
use App\Models\Followup;
use App\Models\Reservation;
use App\Models\CustomerPhoto;
use App\Models\CustomerPhone;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CustomerRequest;
use Illuminate\Database\Eloquent\Builder;

class CustomerController extends Controller
{
    /**
     * 顾客列表
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
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
                'balance'
            ])
            ->with([
                'tags',
                'phones',
                'createUser:id,name',
                'consultantUser:id,name',
                'ascriptionUser:id,name',
            ])
            // 关键词查询
            ->when($category == 'keyword', function (Builder $query) use ($keyword) {
                $query->where('keyword', 'like', "%{$keyword}%");
            })
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
                if (count($ids) == 1) {
                    $query->where(function ($query) use ($ids) {
                        $query->where('ascription', $ids[0])->orWhere('consultant', $ids[0]);
                    });
                } else {
                    $query->where(function ($query) use ($ids) {
                        $query->whereIn('ascription', $ids)->orWhereIn('consultant', $ids);
                    });
                }
            })
            ->orderBy('created_at', 'desc')
            ->paginate($rows);

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
     * 用户画像
     * @param CustomerRequest $request
     * @return JsonResponse
     */
    public function profile(CustomerRequest $request): JsonResponse
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
     * 查询顾客回访记录
     * @param CustomerRequest $request
     * @return JsonResponse
     */
    public function followup(CustomerRequest $request): JsonResponse
    {
        $rows  = $request->input('rows', 10);
        $sort  = $request->input('sort', 'created_at');
        $order = $request->input('order', 'desc');
        $query = Followup::query()
            ->with([
                'followupUserInfo:id,name'
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
     * @param CustomerRequest $request
     * @return JsonResponse
     */
    public function reservation(CustomerRequest $request): JsonResponse
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

    /**
     * 顾客照片
     * @param CustomerRequest $request
     * @return JsonResponse
     */
    public function photo(CustomerRequest $request): JsonResponse
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
}
