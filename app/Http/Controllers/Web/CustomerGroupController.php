<?php

namespace App\Http\Controllers\Web;

use Exception;
use Throwable;
use App\Helpers\ParseCdpField;
use App\Exceptions\HisException;
use App\Http\Controllers\Controller;
use App\Jobs\CustomerGroupComputingJob;
use App\Models\CustomerGroup;
use App\Models\CustomerGroupField;
use App\Models\CustomerGroupDetail;
use App\Models\CustomerGroupCategory;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Builder;
use App\Http\Requests\Web\CustomerGroupRequest;

class CustomerGroupController extends Controller
{
    /**
     * 分群管理
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $rows  = $request->input('rows', 10);
        $sort  = $request->input('sort', 'created_at');
        $order = $request->input('order', 'desc');
        $query = CustomerGroup::query()
            ->with([
                'category',
                'createUser:id,name',
            ])
            ->when($request->input('name'), fn(Builder $query) => $query->where('name', 'like', '%' . $request->input('name') . '%'))
            ->when($request->input('category_id'), fn(Builder $query) => $query->where('category_id', $request->input('category_id')))
            ->orderBy($sort, $order)
            ->paginate($rows);

//        $query->makeHidden(['sql']);

        return response_success([
            'rows'  => $query->items(),
            'total' => $query->total()
        ]);
    }

    /**
     * 客户分群分类
     * @return JsonResponse
     */
    public function categories(): JsonResponse
    {
        $categories = CustomerGroupCategory::query()
            ->orderBy('sort')
            ->orderBy('id')
            ->get();
        return response_success($categories);
    }

    /**
     * 添加分群分类
     * @param CustomerGroupRequest $request
     * @return JsonResponse
     * @throws Throwable
     */
    public function addCategory(CustomerGroupRequest $request): JsonResponse
    {
        DB::beginTransaction();
        try {
            $category = CustomerGroupCategory::query()->create([
                'name'  => $request->input('name'),
                'scope' => $request->input('scope'),
            ]);

            if ($request->input('scope') !== 'all') {
                $category->scopeable()->createMany(
                    collect($request->input('scope_value'))->map(function ($value) use ($request) {
                        return [
                            'scopeable_type' => $request->input('scope'),
                            'scopeable_id'   => $value,
                        ];
                    })->toArray()
                );
            }

            DB::commit();
            return response_success($category);
        } catch (Exception $e) {
            DB::rollBack();
            throw new HisException('添加分群分类失败', $e->getMessage(), $e->getCode());
        }
    }

    /**
     * 更新分群分类
     * @param CustomerGroupRequest $request
     * @return JsonResponse
     * @throws Throwable
     */
    public function updateCategory(CustomerGroupRequest $request): JsonResponse
    {
        DB::beginTransaction();
        try {
            $category = CustomerGroupCategory::query()->find(
                $request->input('id')
            );

            $category->update([
                'name'  => $request->input('name'),
                'scope' => $request->input('scope'),
            ]);

            // 删除旧的 scope 数据
            $category->scopeable()->delete();

            // 如果不是全部可见，则创建新的 scope 数据
            if ($request->input('scope') !== 'all') {
                $category->scopeable()->createMany(
                    collect($request->input('scope_value'))->map(function ($value) use ($request) {
                        return [
                            'scopeable_type' => $request->input('scope'),
                            'scopeable_id'   => $value,
                        ];
                    })->toArray()
                );
            }

            DB::commit();
            return response_success($category);
        } catch (Exception $e) {
            DB::rollBack();
            throw new HisException('更新分群分类失败', $e->getMessage(), $e->getCode());
        }
    }

    /**
     * 删除分群分类
     * @param CustomerGroupRequest $request
     * @return JsonResponse
     * @throws Throwable
     */
    public function removeCategory(CustomerGroupRequest $request): JsonResponse
    {
        DB::beginTransaction();
        try {
            $category = CustomerGroupCategory::query()->find(
                $request->input('id')
            );

            // 删除权限范围数据
            $category->scopeable()->delete();

            // 删除分类
            $category->delete();

            DB::commit();
            return response_success();
        } catch (Exception $e) {
            DB::rollBack();
            throw new HisException('删除分群分类失败', $e->getMessage(), $e->getCode());
        }
    }

    /**
     * 获取分群分类详情
     * @param CustomerGroupRequest $request
     * @return JsonResponse
     */
    public function getCategory(CustomerGroupRequest $request): JsonResponse
    {
        $category = CustomerGroupCategory::query()->find(
            $request->input('id')
        );
        $category->load(['scopeable']);
        return response_success($category);
    }

    /**
     * 交换分群分类顺序
     * @param CustomerGroupRequest $request
     * @return JsonResponse
     */
    public function swapCategory(CustomerGroupRequest $request): JsonResponse
    {
        $category1 = CustomerGroupCategory::query()->find(
            $request->input('id1')
        );
        $category2 = CustomerGroupCategory::query()->find(
            $request->input('id2')
        );
        $update1   = [
            'sort' => $category2->sort,
        ];
        $update2   = [
            'sort' => $category1->sort,
        ];
        $category1->update($update1);
        $category2->update($update2);
        return response_success();
    }

    /**
     * 分群字段
     * @return JsonResponse
     */
    public function fields(): JsonResponse
    {
        $fields = CustomerGroupField::all();
        $data   = $fields->groupBy('table')
            ->map(function ($items, $table) {
                return [
                    'value'    => $table,
                    'label'    => $items->first()->table_name,
                    'children' => $items->map(function ($item) {
                        return [
                            'value'            => $item->field,
                            'label'            => $item->field_name,
                            'keyword'          => $item->keyword,
                            'api'              => $item->api,
                            'component'        => $item->component,
                            'component_params' => $item->component_params,
                            'operators'        => $item->operators,
                        ];
                    })->values()
                ];
            })->values();
        return response_success($data);
    }

    /**
     * 创建顾客分群
     * @param CustomerGroupRequest $request
     * @return JsonResponse
     */
    public function create(CustomerGroupRequest $request): JsonResponse
    {
        $group = CustomerGroup::query()->create(
            $request->formData()
        );
        $group->makeHidden(['sql']);
        return response_success($group);
    }

    /**
     * 更新分群
     * @param CustomerGroupRequest $request
     * @return JsonResponse
     */
    public function update(CustomerGroupRequest $request): JsonResponse
    {
        $group = CustomerGroup::query()->find(
            $request->input('id')
        );
        $group->update(
            $request->formData()
        );
        $group->makeHidden(['sql']);
        return response_success($group);
    }

    /**
     * 删除分群
     * @param CustomerGroupRequest $request
     * @return JsonResponse
     */
    public function remove(CustomerGroupRequest $request): JsonResponse
    {
        CustomerGroup::query()->find($request->input('id'))->delete();
        CustomerGroupDetail::query()->where('customer_group_id', $request->input('id'))->delete();
        return response_success();
    }

    /**
     * 重新计算分群数据
     * @param CustomerGroupRequest $request
     * @return JsonResponse
     */
    public function compute(CustomerGroupRequest $request): JsonResponse
    {
        $group = CustomerGroup::query()->find(
            $request->input('id')
        );

        // 动态分群计算之前重新生成一下sql
        if ($group->type == 'dynamic') {
            $parser     = new ParseCdpField();
            $group->sql = $parser->filter($group->filter_rule)->exclude($group->exclude_rule)->getSql();
            $group->save();
        }

        // 调用任务异步执行
        dispatch(new CustomerGroupComputingJob(
            tenant()->id,
            $group->id,
            $group->sql
        ));

        return response_success($group);
    }

    /**
     * 分群预览
     * @param CustomerGroupRequest $request
     * @return JsonResponse
     */
    public function preview(CustomerGroupRequest $request): JsonResponse
    {
        $rows  = $request->input('rows', 10);
        $query = CustomerGroupDetail::query()
            ->select('customer_group_details.*')
            ->with([
                'customer:id,name,idcard,sex,age,ascription,consultant,created_at',
                'customer.consultantUser:id,name',
                'customer.ascriptionUser:id,name',
            ])
            ->where('customer_group_details.customer_group_id', $request->input('id'))
            ->when($request->input('keyword'), function (Builder $query) use ($request) {
                $query->leftJoin('customer', 'customer.id', '=', 'customer_group_details.customer_id')
                    ->where('customer.keyword', 'like', '%' . $request->input('keyword') . '%');
            })
            ->orderByDesc('customer_group_details.created_at')
            ->paginate($rows);

        return response_success([
            'rows'  => $query->items(),
            'total' => $query->total()
        ]);
    }

    /**
     * 静态分群 - 导入顾客
     * @param CustomerGroupRequest $request
     * @return JsonResponse
     */
    public function import(CustomerGroupRequest $request): JsonResponse
    {
        $group = CustomerGroup::query()->find(
            $request->input('customer_group_id')
        );

        // 单个导入顾客
        if ($request->input('type') === 'single') {
            $request->importBySingle($group->id);
        }

        // 在线导入,前端传rows数组过来
        if ($request->input('type') === 'online') {
            $request->importByOnline($group->id);
        }

        // 查询导入
        if ($request->input('type') === 'query') {
            $request->importByQuery($group->id);
        }

        // 文件导入
        if ($request->input('type') === 'file') {
            $request->importByFile($group->id);
        }

        // 调用任务异步执行
        return response_success($group);
    }


    /**
     * 静态分群 - 移出顾客
     * @param CustomerGroupRequest $request
     * @return JsonResponse
     */
    public function removeCustomer(CustomerGroupRequest $request): JsonResponse
    {
        CustomerGroupDetail::query()
            ->where('customer_group_id', $request->input('customer_group_id'))
            ->whereIn('customer_id', $request->input('ids'))
            ->delete();
        return response_success();
    }

    /**
     * 复制分群
     * @param CustomerGroupRequest $request
     * @return JsonResponse
     */
    public function copy(CustomerGroupRequest $request): JsonResponse
    {
        $group = CustomerGroup::query()->find(
            $request->input('id')
        );

        $newGroup                    = $group->replicate();
        $newGroup->name              = $group->name . '-副本';
        $newGroup->count             = 0;
        $newGroup->last_execute_time = null;
        $newGroup->save();

        return response_success($newGroup);
    }
}
