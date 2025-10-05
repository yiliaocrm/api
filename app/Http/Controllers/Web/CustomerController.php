<?php

namespace App\Http\Controllers\Web;

use Exception;
use Throwable;
use App\Models\Customer;
use App\Models\CustomerPhone;
use App\Imports\CustomerImport;
use App\Exceptions\HisException;
use App\Services\CustomerService;
use App\Http\Controllers\Controller;
use App\Models\CustomerGroupCategory;
use App\Http\Requests\Web\CustomerRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Builder;

class CustomerController extends Controller
{
    public CustomerService $customerService;

    public function __construct(CustomerService $service)
    {
        $this->customerService = $service;
    }

    /**
     * 会员列表
     * @param CustomerRequest $request
     * @return JsonResponse
     */
    public function index(CustomerRequest $request): JsonResponse
    {
        $rows     = $request->input('rows', 10);
        $sort     = $request->input('sort', 'created_at');
        $order    = $request->input('order', 'desc');
        $keyword  = $request->input('keyword');
        $group_id = $request->input('group_id', 'all') === 'all' ? null : $request->input('group_id');

        $query = Customer::query()
            ->with([
                'items',
                'phone',
                'medium:id,name',
                'doctorUser:id,name',
                'serviceUser:id,name',
                'consultantUser:id,name',
                'ascriptionUser:id,name',
                'referrerUser:id,name',
                'referrerCustomer:id,name,idcard',
            ])
            ->select([
                'customer.*'
            ])
            ->when($keyword, fn(Builder $query) => $query->where('keyword', 'like', "%{$keyword}%"))
            ->when($group_id, fn(Builder $query) => $query->leftJoin('customer_group_details', 'customer_group_details.customer_id', '=', 'customer.id')
                ->where('customer_group_details.customer_group_id', $group_id)
            )
            ->queryConditions('CustomerIndex')
            // 权限限制
            ->when(!user()->hasAnyAccess(['superuser', 'customer.view.all']), function (Builder $query) {
                $ids = user()->getCustomerViewUsersPermission();
                $query->where(function ($query) use ($ids) {
                    $query->whereIn('ascription', $ids)->orWhereIn('consultant', $ids);
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
     * 顾客管理首页分群信息
     * @return JsonResponse
     */
    public function groups()
    {
        $categories = CustomerGroupCategory::query()
            ->with([
                'groups:id,name,type,category_id,count,remark'
            ])
            // 可见范围过滤
            ->when(!user()->isSuperUser(), function ($query) {
                $query->where(function ($query) {
                    $query->where('scope', 'all')
                        ->orWhere(function ($query) {
                            $query->where('scope', 'departments')
                                ->whereHas('scopeable', function ($query) {
                                    $query->where('scopeable_type', 'departments')
                                        ->where('scopeable_id', user()->department_id);
                                });
                        })
                        ->orWhere(function ($query) {
                            $query->where('scope', 'users')
                                ->whereHas('scopeable', function ($query) {
                                    $query->where('scopeable_type', 'users')
                                        ->where('scopeable_id', user()->id);
                                });
                        });
                });
            })
            ->orderBy('sort')
            ->orderBy('id')
            ->get();
        return response_success($categories);
    }

    /**
     * 创建顾客信息
     * @param CustomerRequest $request
     * @return JsonResponse
     * @throws HisException|Throwable
     */
    public function create(CustomerRequest $request): JsonResponse
    {
        DB::beginTransaction();

        try {

            $customer = Customer::query()->create(
                $request->formData()
            );

            // 创建号码
            $customer->phones()->createMany(
                $request->input('phones')
            );

            // 创建顾客标签
            $customer->tags()->attach($request->input('tags', []));

            // 加载相关数据
            $customer->load([
                'items',
                'phones',
                'medium:id,name',
                'consultantUser:id,name',
                'ascriptionUser:id,name',
                'referrerUser:id,name',
                'referrerCustomer:id,name,idcard',
            ]);

            DB::commit();
            return response_success($customer);

        } catch (Exception $e) {
            DB::rollBack();
            throw new HisException($e->getMessage());
        }
    }

    /**
     * 更新顾客信息
     * @param CustomerRequest $request
     * @return JsonResponse
     * @throws HisException|Throwable
     */
    public function update(CustomerRequest $request): JsonResponse
    {
        DB::beginTransaction();

        try {

            $customer = Customer::query()->find(
                $request->input('id')
            );

            // 有权限更新电话
            if (user()->hasAnyAccess(['superuser', 'customer.update.phone'])) {
                $phoneChanges = $request->getPhoneChanges($customer);

                // 1. 删除电话
                if (!empty($phoneChanges['delete'])) {
                    $customer->phones()->whereIn('id', $phoneChanges['delete'])->delete();
                }

                // 2. 新增电话
                if (!empty($phoneChanges['add'])) {
                    $customer->phones()->createMany($phoneChanges['add']);
                }

                // 3. 更新电话
                if (!empty($phoneChanges['update'])) {
                    foreach ($phoneChanges['update'] as $phoneData) {
                        $customer->phones()->where('id', $phoneData['id'])->update($phoneData);
                    }
                }
            }

            // 同步标签
            $customer->tags()->sync($request->input('tags', []));

            // 更新顾客信息
            $customer->update(
                $request->formData($customer)
            );

            $customer->load([
                'job:id,name',
                'tags',
                'phones',
                'economic:id,name',
                'consultantUser:id,name',
                'ascriptionUser:id,name',
                'referrerUser:id,name',
                'referrerCustomer:id,name,idcard',
            ]);

            $customer->medium_name  = get_medium_name($customer->medium_id, true);
            $customer->address_name = get_address_name($customer->address_id, true);

            // 提交事务
            DB::commit();
            return response_success($customer);

        } catch (Exception $e) {
            DB::rollBack();
            throw new HisException($e->getMessage());
        }
    }

    /**
     * 填充信息
     * @param CustomerRequest $request
     * @return JsonResponse
     */
    public function fill(CustomerRequest $request): JsonResponse
    {
        return response_success(
            $this->customerService->fill(
                $request->input('customer_id')
            )
        );
    }

    /**
     * 删除顾客档案
     * @param CustomerRequest $request
     * @return JsonResponse
     * @throws HisException|Throwable
     */
    public function remove(CustomerRequest $request): JsonResponse
    {
        DB::beginTransaction();
        try {
            Customer::query()->find($request->input('id'))->remove();
            DB::commit();
            return response_success();
        } catch (Exception $e) {
            DB::rollBack();
            throw new HisException($e->getMessage());
        }
    }

    /**
     * 普通查询(通过关键词查询顾客)
     * @param CustomerRequest $request
     * @return JsonResponse
     */
    public function query(CustomerRequest $request): JsonResponse
    {
        $id       = $request->input('id');
        $rows     = $request->input('rows', 10);
        $keyword  = $request->input('keyword');
        $category = $request->input('category', 'keyword');

        $query = Customer::query()
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
                'phones',
                'consultantUser:id,name',
                'ascriptionUser:id,name'
            ])
            // 加载指定顾客
            ->when($id, fn(Builder $query) => $query->where('id', $id))
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
            ->orderBy('id', 'desc')
            ->paginate($rows);

        return response_success([
            'rows'  => $query->items(),
            'total' => $query->total()
        ]);
    }

    /**
     * 合并客户请求
     * @param CustomerRequest $request
     * @return JsonResponse
     * @throws HisException|Throwable
     */
    public function merge(CustomerRequest $request): JsonResponse
    {
        DB::beginTransaction();
        try {
            // 查询顾客
            $customer = Customer::query()->find(
                $request->input('customer_id')
            );

            // 执行合并操作
            $customer->merge($request->input('customer_id2'));

            // 重新加载客户信息（包含电话号码）
            $customer->load('phones');

            // 更新搜索关键词
            $customer->update([
                'keyword' => Customer::generateKeyword($customer->toArray(), $customer->phones->pluck('phone')->toArray())
            ]);

            DB::commit();
            return response_success();
        } catch (Exception $e) {
            DB::rollBack();
            throw new HisException($e->getMessage());
        }
    }

    /**
     * 导入顾客信息
     * @param CustomerRequest $request
     * @return JsonResponse
     */
    public function import(CustomerRequest $request): JsonResponse
    {
        (new CustomerImport)->import($request->file('excel'));
        return response_success();
    }

    /**
     * 查询会员生日
     * @param Request $request
     * @return JsonResponse
     */
    public function birthday(Request $request): JsonResponse
    {
        $rows  = $request->input('rows', 10);
        $sort  = $request->input('sort', 'created_at');
        $order = $request->input('order', 'desc');
        $query = Customer::query()
            ->when($request->input('birthday_start') && $request->input('birthday_end'), function (Builder $query) use ($request) {
                $query->whereBetween(DB::raw("DATE_FORMAT( birthday, '%m-%d' )"), [
                    $request->input('birthday_start'),
                    $request->input('birthday_end')
                ]);
            })
            ->whereNotNull('birthday')
            // 权限限制
            ->when(!user()->hasAnyAccess(['superuser', 'customer.view.all']), function ($query) {
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
            ->orderBy($sort, $order)
            ->paginate($rows);

        return response_success([
            'rows'  => $query->items(),
            'total' => $query->total()
        ]);
    }
}
