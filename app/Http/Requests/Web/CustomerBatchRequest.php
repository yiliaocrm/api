<?php

namespace App\Http\Requests\Web;

use App\Models\Sms;
use App\Enums\SmsStatus;
use App\Jobs\SendSmsJob;
use App\Models\Customer;
use App\Models\CustomerPhone;
use App\Rules\Web\SceneRule;
use App\Jobs\BatchSendSmsJob;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Database\Eloquent\Builder;

class CustomerBatchRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return match (request()->route()->getActionMethod()) {
            'sms' => $this->getSmsRules(),
            'tags' => $this->getTagsRules(),
            'followup' => $this->getFollowupRules(),
            'joinGroup', 'removeGroup', 'changeGroup' => $this->getJoinGroupRules(),
            'ascription', 'consultant', 'doctor', 'service' => $this->getAscriptionRules(),
            default => [],
        };
    }

    public function messages(): array
    {
        return match (request()->route()->getActionMethod()) {
            'sms' => $this->getSmsMessages(),
            'tags' => $this->getTagsMessages(),
            'followup' => $this->getFollowupMessages(),
            'joinGroup', 'removeGroup', 'changeGroup' => $this->getJoinGroupMessages(),
            'ascription', 'consultant', 'doctor', 'service' => $this->getAscriptionMessages(),
            default => [],
        };
    }

    /**
     * 批量修改开发员验证规则
     * @return array
     */
    private function getAscriptionRules(): array
    {
        $ids   = $this->input('ids', []);
        $isall = $this->input('isall', false);
        $rules = [
            'isall'              => 'required|boolean',
            'users'              => 'required|array',
            'users.*'            => 'required|exists:users,id',
            'limit'              => 'required|numeric|min:0',
            'distribution_rules' => 'required|in:alternation,equally',
        ];

        // 全选
        if ($isall) {
            $rules['filters']  = ['nullable', 'array', new SceneRule('CustomerIndex')];
            $rules['group_id'] = $this->input('group_id') === 'all' ? 'required|in:all' : 'required|exists:customer_groups,id';
        }

        // 指定ids
        if (!empty($ids)) {
            $rules['ids']   = 'required|array';
            $rules['ids.*'] = 'required|exists:customer,id';
        }

        return $rules;
    }

    /**
     * 批量修改开发员验证消息
     * @return string[]
     */
    private function getAscriptionMessages(): array
    {
        return [
            'limit.required'        => '[分配数量]不能为空!',
            'limit.numeric'         => '[分配数量]必须为数字!',
            'limit.min'             => '[分配数量]不能小于0!',
            'distribution_rules.in' => '[分配规则]错误!',
            'users.required'        => '[分配人员]不能为空!',
            'users.exists'          => '[分配人员]不存在!',
            'isall.required'        => '[是否全部]不能为空!',
            'isall.boolean'         => '[是否全部]必须为布尔值!',
            'ids.required'          => '[客户ID]不能为空!',
            'ids.array'             => '[客户ID]必须为数组!',
            'ids.*.required'        => '[客户ID]不能为空!',
            'ids.*.exists'          => '[客户ID]不存在!',
            'group_id.required'     => '[分组ID]不能为空!',
            'group_id.exists'       => '[分组ID]不存在!',
            'group_id.in'           => '[分组ID]错误!',
        ];
    }

    /**
     * 批量设置回访验证规则
     * @return string[]
     */
    private function getFollowupRules(): array
    {
        $ids   = $this->input('ids', []);
        $role  = $this->input('followup_role');
        $isall = $this->input('isall', false);
        $rules = [
            'isall'              => 'required|boolean',
            'form'               => 'required|array',
            'form.title'         => 'required|string|max:255',
            'form.date'          => 'required|date_format:Y-m-d',
            'form.type'          => 'required|exists:followup_type,id',
            'form.user_id'       => 'required_if:form.followup_role,followup_role_user|exists:users,id',
            'form.followup_role' => 'required|in:followup_role_user,followup_role_ascription,followup_role_consultant'
        ];

        // 全选
        if ($isall) {
            $rules['filters']  = ['nullable', 'array', new SceneRule('CustomerIndex')];
            $rules['group_id'] = $this->input('group_id') === 'all' ? 'required|in:all' : 'required|exists:customer_groups,id';
        }

        // 指定ids
        if (!empty($ids)) {
            $rules['ids']   = 'required|array';
            $rules['ids.*'] = 'required|exists:customer,id';
        }

        // [指定人员]回访
        if ($role == 'followup_role_user') {
            $rules['user_id'] = 'required|exists:users,id';
        }

        return $rules;
    }

    private function getFollowupMessages(): array
    {
        return [
            'isall.required'              => '[选择全部]不能为空!',
            'isall.boolean'               => '[选择全部]必须为布尔值!',
            'form.title.required'         => '[回访标题]不能为空!',
            'form.title.string'           => '[回访标题]必须为字符串!',
            'form.title.max'              => '[回访标题]最大长度为255!',
            'form.date.required'          => '[回访日期]不能为空!',
            'form.date.date_format'       => '[回访日期]格式错误!',
            'form.type.required'          => '[回访类型]不能为空!',
            'form.type.exists'            => '[回访类型]不存在!',
            'form.user_id.required_if'    => '[回访人员]不能为空!',
            'form.user_id.exists'         => '[回访人员]不存在!',
            'form.followup_role.required' => '[回访角色]不能为空!',
            'form.followup_role.in'       => '[回访角色]错误!',
            'ids.required'                => '[客户ID]不能为空!',
            'ids.array'                   => '[客户ID]必须为数组!',
            'ids.*.required'              => '[客户ID]不能为空!',
            'ids.*.exists'                => '[客户ID]不存在!',
            'group_id.required'           => '[分组ID]不能为空!',
            'group_id.exists'             => '[分组ID]不存在!',
            'group_id.in'                 => '[分组ID]错误!',
        ];
    }

    private function getTagsRules(): array
    {

        $ids   = $this->input('ids', []);
        $isall = $this->input('isall', false);
        $rules = [
            'isall'  => 'required|boolean',
            'tags'   => 'required|array',
            'tags.*' => 'required|exists:tags,id',
            'rules'  => 'required|in:add,remove',
        ];

        // 全选
        if ($isall) {
            $rules['filters']  = ['nullable', 'array', new SceneRule('CustomerIndex')];
            $rules['group_id'] = $this->input('group_id') === 'all' ? 'required|in:all' : 'required|exists:customer_groups,id';
        }

        // 指定ids
        if (!empty($ids)) {
            $rules['ids']   = 'required|array';
            $rules['ids.*'] = 'required|exists:customer,id';
        }

        return $rules;
    }

    private function getTagsMessages(): array
    {
        return [
            'isall.required'    => '[选择全部]不能为空!',
            'isall.boolean'     => '[选择全部]必须为布尔值!',
            'tags.required'     => '[标签]不能为空!',
            'tags.array'        => '[标签]必须为数组!',
            'tags.*.required'   => '[标签]不能为空!',
            'tags.*.exists'     => '[标签]不存在!',
            'rules.required'    => '[规则]不能为空!',
            'rules.in'          => '[规则]错误!',
            'ids.required'      => '[客户ID]不能为空!',
            'ids.array'         => '[客户ID]必须为数组!',
            'ids.*.required'    => '[客户ID]不能为空!',
            'ids.*.exists'      => '[客户ID]不存在!',
            'group_id.required' => '[分组ID]不能为空!',
            'group_id.exists'   => '[分组ID]不存在!',
            'group_id.in'       => '[分组ID]错误!',
        ];
    }

    /**
     * 加入分组验证规则
     * @return string[]
     */
    private function getJoinGroupRules(): array
    {
        $ids   = $this->input('ids', []);
        $isall = $this->input('isall', false);
        $rules = [
            'isall'             => 'required|boolean',
            'customer_group_id' => 'required|exists:customer_groups,id',
        ];

        // 全选
        if ($isall) {
            $rules['filters']  = ['nullable', 'array', new SceneRule('CustomerIndex')];
            $rules['group_id'] = $this->input('group_id') === 'all' ? 'required|in:all' : 'required|exists:customer_groups,id';
        }

        // 指定ids
        if (!empty($ids)) {
            $rules['ids']   = 'required|array';
            $rules['ids.*'] = 'required|exists:customer,id';
        }

        return $rules;
    }

    /**
     * 加入分组验证消息
     * @return string[]
     */
    private function getJoinGroupMessages(): array
    {
        return [
            'isall.required'             => '[选择全部]不能为空!',
            'isall.boolean'              => '[选择全部]必须为布尔值!',
            'customer_group_id.required' => '[分组ID]不能为空!',
            'customer_group_id.exists'   => '[分组ID]不存在!',
            'ids.required'               => '[客户ID]不能为空!',
            'ids.array'                  => '[客户ID]必须为数组!',
            'ids.*.required'             => '[客户ID]不能为空!',
            'ids.*.exists'               => '[客户ID]不存在!',
            'group_id.required'          => '[分组ID]不能为空!',
            'group_id.exists'            => '[分组ID]不存在!',
            'group_id.in'                => '[分组ID]错误!',
        ];
    }

    /**
     * 勾选顾客ID批量设置回访
     * @return void
     */
    public function setFollowupByIds(): void
    {
        $ids     = $this->input('ids', []);
        $now     = now()->toDateTimeString();
        $prefix  = DB::getTablePrefix();
        $action  = addslashes(request()->route()->getActionName());
        $user_id = user()->id;

        $subQuery = Customer::query()
            ->select([
                DB::raw('uuid()'),
                'customer.id',
                DB::raw("'{$this->input('form.type')}'"),
                DB::raw(1),
                DB::raw("'{$this->input('form.title')}'"),
                DB::raw("'{$this->input('form.date')}'"),
            ])
            // 指定人员回访
            ->when($this->input('form.followup_role') == 'followup_role_user', function (Builder $builder) {
                $builder->addSelect(DB::raw("'{$this->input('form.user_id')}'"));
            })
            // 归属开发回访
            ->when($this->input('form.followup_role') == 'followup_role_ascription', function (Builder $builder) use ($prefix) {
                $builder->addSelect(DB::raw($prefix . 'customer.ascription'))->whereNotNull('customer.ascription');
            })
            // 归属现场回访
            ->when($this->input('form.followup_role') == 'followup_role_consultant', function (Builder $builder) use ($prefix) {
                $builder->addSelect(DB::raw($prefix . 'customer.consultant'))->whereNotNull('customer.consultant');
            })
            ->addSelect([
                DB::raw($user_id),
                DB::raw("'{$now}'")
            ])
            ->whereIn('id', $ids);

        // 设置回访
        DB::table('followup')->insertUsing(
            ['id', 'customer_id', 'type', 'status', 'title', 'date', 'followup_user', 'user_id', 'created_at'],
            $subQuery
        );

        // 写入日志
        DB::table('customer_log')->insertUsing(
            ['customer_id', 'action', 'user_id', 'logable_id', 'logable_type', 'original', 'dirty', 'created_at', 'updated_at'],
            DB::table('followup')
                ->select([
                    'customer_id',
                    DB::raw("'{$action}' AS action"),
                    DB::raw("'{$user_id}' as user_id"),
                    "id as logable_id",
                    DB::raw("'App\\\\Models\\\\Followup' AS logable_type"),
                    DB::raw('null AS original'),
                    DB::raw('null AS dirty'),
                    DB::raw("'{$now}' AS created_at"),
                    DB::raw("'{$now}' AS updated_at")
                ])
                ->where('created_at', $now)
                ->whereIn('customer_id', $ids)
        );
    }

    /**
     * 全选批量设置回访
     * @return void
     */
    public function setFollowupByAll(): void
    {
        $now      = now()->toDateTimeString();
        $action   = addslashes(request()->route()->getActionName());
        $prefix   = DB::getTablePrefix();
        $user_id  = user()->id;
        $sort     = $this->input('sort', 'created_at');
        $order    = $this->input('order', 'desc');
        $keyword  = $this->input('keyword');
        $group_id = $this->input('group_id', 'all') === 'all' ? null : $this->input('group_id');

        $query = Customer::query()
            // 关键字搜索
            ->when($keyword, fn(Builder $query) => $query->where('customer.keyword', 'like', "%{$keyword}%"))
            ->when($group_id, fn(Builder $query) => $query->leftJoin('customer_group_details', 'customer_group_details.customer_id', '=', 'customer.id')
                ->where('customer_group_details.customer_group_id', $group_id)
            )
            // 权限限制
            ->when(!user()->hasAnyAccess(['superuser', 'customer.view.all']), function (Builder $query) {
                $ids = user()->getCustomerViewUsersPermission();
                $query->where(function ($query) use ($ids) {
                    $query->whereIn('customer.ascription', $ids)->orWhereIn('customer.consultant', $ids);
                });
            })
            ->queryConditions('CustomerIndex')
            ->orderBy('customer.' . $sort, $order);

        // 复制查询条件
        $subQuery = clone $query;

        // 指定人员回访
        $subQuery
            ->select([
                DB::raw('uuid()'),
                'customer.id',
                DB::raw("'{$this->input('form.type')}'"),
                DB::raw(1),
                DB::raw("'{$this->input('form.title')}'"),
                DB::raw("'{$this->input('form.date')}'"),
            ])
            ->when($this->input('form.followup_role') == 'followup_role_user', function (Builder $builder) {
                $builder->addSelect(DB::raw("'{$this->input('form.user_id')}'"));
            })
            // 归属开发回访
            ->when($this->input('form.followup_role') == 'followup_role_ascription', function (Builder $builder) use ($prefix) {
                $builder->addSelect(DB::raw($prefix . 'customer.ascription'))->whereNotNull('customer.ascription');
            })
            // 归属现场回访
            ->when($this->input('form.followup_role') == 'followup_role_consultant', function (Builder $builder) use ($prefix) {
                $builder->addSelect(DB::raw($prefix . 'customer.consultant'))->whereNotNull('customer.consultant');
            })
            ->addSelect([
                DB::raw($user_id),
                DB::raw("'{$now}'")
            ]);

        // 设置回访
        DB::table('followup')->insertUsing(
            ['id', 'customer_id', 'type', 'status', 'title', 'date', 'followup_user', 'user_id', 'created_at'],
            $subQuery
        );

        // 特殊处理下字段
        $query->select('customer.id');

        // 写入日志
        DB::table('customer_log')->insertUsing(
            ['customer_id', 'action', 'user_id', 'logable_id', 'logable_type', 'original', 'dirty', 'created_at', 'updated_at'],
            DB::table('followup')
                ->select([
                    'customer_id',
                    DB::raw("'{$action}' AS action"),
                    DB::raw("'{$user_id}' as user_id"),
                    "followup.id as logable_id",
                    DB::raw("'App\\\\Models\\\\Followup' AS logable_type"),
                    DB::raw('null AS original'),
                    DB::raw('null AS dirty'),
                    DB::raw("'{$now}' AS created_at"),
                    DB::raw("'{$now}' AS updated_at")
                ])
                ->joinSub($query, 'c', function ($join) use ($now) {
                    $join->on('followup.customer_id', '=', 'c.id')->where('followup.created_at', $now);
                })
        );
    }

    /**
     * 勾选顾客ID新增标签
     * @return void
     */
    public function addTagsByIds(): void
    {
        $ids     = $this->input('ids', []);
        $now     = now()->toDateTimeString();
        $tags    = $this->input('tags', []);
        $action  = addslashes(request()->route()->getActionName());
        $user_id = user()->id;

        // 定义基础查询，获取需要插入的 customer_id 和 tags_id
        $subQuery = DB::table('tags')
            ->select([
                'tags.id as tags_id',
                'customer.id as customer_id',
                DB::raw("'{$now}' as created_at"),
                DB::raw("'{$now}' as updated_at")
            ])
            ->crossJoin('customer')
            ->leftJoin('customer_tags', function ($join) {
                $join->on('customer_tags.tags_id', '=', 'tags.id')
                    ->on('customer_tags.customer_id', '=', 'customer.id');
            })
            ->whereIn('tags.id', $tags)
            ->whereIn('customer.id', $ids)
            ->whereNull('customer_tags.id');

        // 写入日志(缺失变更前后的标签)
        DB::table('customer_log')->insertUsing(
            ['customer_id', 'action', 'user_id', 'logable_id', 'logable_type', 'original', 'dirty', 'created_at', 'updated_at'],
            DB::query()
                ->fromSub($subQuery, 'sub')
                ->select([
                    'customer_id',
                    DB::raw("'{$action}' AS action"),
                    DB::raw("'{$user_id}' as user_id"),
                    'tags_id as logable_id',
                    DB::raw("'App\\\\Models\\\\Tags' AS logable_type"),
                    DB::raw('null AS original'),
                    DB::raw('null AS dirty'),
                    DB::raw("'{$now}' AS created_at"),
                    DB::raw("'{$now}' AS updated_at")
                ])
        );

        // 批量插入标签
        DB::table('customer_tags')->insertUsing(
            ['tags_id', 'customer_id', 'created_at', 'updated_at'],
            $subQuery
        );
    }

    /**
     * 勾选顾客ID删除标签
     * @return void
     */
    public function removeTagsByIds(): void
    {
        $ids     = $this->input('ids', []);
        $now     = now()->toDateTimeString();
        $tags    = $this->input('tags', []);
        $action  = addslashes(request()->route()->getActionName());
        $user_id = user()->id;

        // 定义基础查询，获取需要删除的 customer_id 和 tags_id
        $subQuery = DB::table('customer_tags')
            ->select([
                'customer_tags.customer_id',
                'customer_tags.tags_id',
                DB::raw("'{$now}' as created_at"),
                DB::raw("'{$now}' as updated_at")
            ])
            ->whereIn('customer_tags.customer_id', $ids)
            ->whereIn('customer_tags.tags_id', $tags);

        // 写入日志(缺失变更前后的标签)
        DB::table('customer_log')->insertUsing(
            ['customer_id', 'action', 'user_id', 'logable_id', 'logable_type', 'original', 'dirty', 'created_at', 'updated_at'],
            DB::query()
                ->fromSub($subQuery, 'sub')
                ->select([
                    'customer_id',
                    DB::raw("'{$action}' AS action"),
                    DB::raw("'{$user_id}' as user_id"),
                    'tags_id as logable_id',
                    DB::raw("'App\\\\Models\\\\Tags' AS logable_type"),
                    DB::raw('null AS original'),
                    DB::raw('null AS dirty'),
                    DB::raw("'{$now}' AS created_at"),
                    DB::raw("'{$now}' AS updated_at")
                ])
        );

        // 批量删除标签
        DB::table('customer_tags')
            ->whereIn('customer_id', $ids)
            ->whereIn('tags_id', $tags)
            ->delete();
    }

    /**
     * 全选设置标签
     * @return void
     */
    /**
     * 全选设置标签
     * @return void
     */
    public function addTagsByAll(): void
    {
        $sort     = $this->input('sort', 'created_at');
        $order    = $this->input('order', 'desc');
        $tags     = $this->input('tags', []);
        $keyword  = $this->input('keyword');
        $now      = now()->toDateTimeString();
        $action   = addslashes(request()->route()->getActionName());
        $user_id  = user()->id;
        $group_id = $this->input('group_id', 'all') === 'all' ? null : $this->input('group_id');

        // 构建基础客户查询
        $query = Customer::query()
            ->select('customer.id')
            ->when($keyword, fn(Builder $query) => $query->where('customer.keyword', 'like', "%{$keyword}%"))
            ->when($group_id, fn(Builder $query) => $query->leftJoin('customer_group_details', 'customer_group_details.customer_id', '=', 'customer.id')
                ->where('customer_group_details.customer_group_id', $group_id)
            )
            ->queryConditions('CustomerIndex')
            // 权限限制
            ->when(!user()->hasAnyAccess(['superuser', 'customer.view.all']), function (Builder $query) {
                $ids = user()->getCustomerViewUsersPermission();
                $query->where(function ($query) use ($ids) {
                    $query->whereIn('customer.ascription', $ids)->orWhereIn('customer.consultant', $ids);
                });
            })
            ->orderBy('customer.' . $sort, $order);

        // 定义新增标签的子查询
        $subQuery = DB::table('tags')
            ->select([
                'tags.id as tags_id',
                'customer.id as customer_id',
                DB::raw("'{$now}' as created_at"),
                DB::raw("'{$now}' as updated_at")
            ])
            ->crossJoinSub($query, 'customer')
            ->leftJoin('customer_tags', function ($join) {
                $join->on('customer_tags.tags_id', '=', 'tags.id')
                    ->on('customer_tags.customer_id', '=', 'customer.id');
            })
            ->whereIn('tags.id', $tags)
            ->whereNull('customer_tags.id');

        // 写入日志
        DB::table('customer_log')->insertUsing(
            ['customer_id', 'action', 'user_id', 'logable_id', 'logable_type', 'original', 'dirty', 'created_at', 'updated_at'],
            DB::query()
                ->fromSub($subQuery, 'sub')
                ->select([
                    'customer_id',
                    DB::raw("'{$action}' AS action"),
                    DB::raw("'{$user_id}' as user_id"),
                    'tags_id as logable_id',
                    DB::raw("'App\\\\Models\\\\Tags' AS logable_type"),
                    DB::raw('null AS original'),
                    DB::raw('null AS dirty'),
                    DB::raw("'{$now}' AS created_at"),
                    DB::raw("'{$now}' AS updated_at")
                ])
        );

        // 批量插入标签
        DB::table('customer_tags')->insertUsing(
            ['tags_id', 'customer_id', 'created_at', 'updated_at'],
            $subQuery
        );
    }

    /**
     * 全选删除标签
     * @return void
     */
    public function removeTagsByAll(): void
    {
        $now      = now()->toDateTimeString();
        $tags     = $this->input('tags', []);
        $action   = addslashes(request()->route()->getActionName());
        $user_id  = user()->id;
        $sort     = $this->input('sort', 'created_at');
        $order    = $this->input('order', 'desc');
        $keyword  = $this->input('keyword');
        $group_id = $this->input('group_id', 'all') === 'all' ? null : $this->input('group_id');

        // Build the base customer query with filters and permissions
        $customerQuery = Customer::query()
            ->select('customer.id')
            ->when($keyword, fn(Builder $query) => $query->where('customer.keyword', 'like', "%{$keyword}%"))
            ->when($group_id, fn(Builder $query) => $query->leftJoin('customer_group_details', 'customer_group_details.customer_id', '=', 'customer.id')
                ->where('customer_group_details.customer_group_id', $group_id)
            )
            ->queryConditions('CustomerIndex')
            // Apply permission limitations
            ->when(!user()->hasAnyAccess(['superuser', 'customer.view.all']), function (Builder $query) {
                $ids = user()->getCustomerViewUsersPermission();
                $query->where(function ($query) use ($ids) {
                    $query->whereIn('customer.ascription', $ids)->orWhereIn('customer.consultant', $ids);
                });
            })
            ->orderBy('customer.' . $sort, $order);

        // Prepare the subquery to select customer_tags to delete
        $subQuery = DB::table('customer_tags')
            ->select([
                'customer_tags.customer_id',
                'customer_tags.tags_id',
                DB::raw("'{$now}' as created_at"),
                DB::raw("'{$now}' as updated_at")
            ])
            ->joinSub($customerQuery, 'customer', function ($join) {
                $join->on('customer_tags.customer_id', '=', 'customer.id');
            })
            ->whereIn('customer_tags.tags_id', $tags);

        // Insert logs into customer_log
        DB::table('customer_log')->insertUsing(
            ['customer_id', 'action', 'user_id', 'logable_id', 'logable_type', 'original', 'dirty', 'created_at', 'updated_at'],
            DB::query()
                ->fromSub($subQuery, 'sub')
                ->select([
                    'customer_id',
                    DB::raw("'{$action}' AS action"),
                    DB::raw("'{$user_id}' as user_id"),
                    'tags_id as logable_id',
                    DB::raw("'App\\\\Models\\\\Tags' AS logable_type"),
                    DB::raw('null AS original'),
                    DB::raw('null AS dirty'),
                    DB::raw("'{$now}' AS created_at"),
                    DB::raw("'{$now}' AS updated_at")
                ])
        );

        // Delete from customer_tags where customer_id matches and tags_id is in the given tags
        DB::table('customer_tags')
            ->whereIn('tags_id', $tags)
            ->whereExists(function ($query) use ($customerQuery) {
                $query->select(DB::raw(1))
                    ->fromSub($customerQuery, 'customer')
                    ->whereColumn('customer.id', 'customer_tags.customer_id');
            })
            ->delete();
    }

    /**
     * 勾选加入分组
     * @return void
     */
    public function joinGroupByIds(): void
    {
        $ids               = $this->input('ids', []);
        $customer_group_id = $this->input('customer_group_id');
        $now               = now()->toDateTimeString();

        // Define a subquery to select customer IDs that are not already in the customer group
        $subQuery = DB::table('customer')
            ->select([
                'customer.id as customer_id',
                DB::raw("'{$customer_group_id}' as customer_group_id"),
                DB::raw("'{$now}' as created_at"),
                DB::raw("'{$now}' as updated_at"),
            ])
            ->leftJoin('customer_group_details', function ($join) use ($customer_group_id) {
                $join->on('customer_group_details.customer_id', '=', 'customer.id')
                    ->where('customer_group_details.customer_group_id', '=', $customer_group_id);
            })
            ->whereIn('customer.id', $ids)
            ->whereNull('customer_group_details.customer_id');

        // Insert new customer-group associations
        DB::table('customer_group_details')->insertUsing(
            ['customer_id', 'customer_group_id', 'created_at', 'updated_at'],
            $subQuery
        );

        // 更新分组数量
        DB::table('customer_groups')
            ->where('id', $customer_group_id)
            ->update([
                'count' => DB::table('customer_group_details')->where('customer_group_id', $customer_group_id)->count()
            ]);
    }

    /**
     * 全选加入分组
     * @return void
     */
    /**
     * 全选加入分组
     * @return void
     */
    public function joinGroupByAll(): void
    {
        $customer_group_id = $this->input('customer_group_id');
        $now               = now()->toDateTimeString();
        $sort              = $this->input('sort', 'created_at');
        $order             = $this->input('order', 'desc');
        $keyword           = $this->input('keyword');
        $group_id          = $this->input('group_id', 'all') === 'all' ? null : $this->input('group_id');

        // 构建基础客户查询
        $customerQuery = Customer::query()
            ->select('customer.id')
            ->when($keyword, function (Builder $query) use ($keyword) {
                $query->where('customer.keyword', 'like', "%{$keyword}%");
            })
            ->when($group_id, function (Builder $query) use ($group_id) {
                $query->leftJoin('customer_group_details as cgd', 'cgd.customer_id', '=', 'customer.id')
                    ->where('cgd.customer_group_id', $group_id);
            })
            ->queryConditions('CustomerIndex')
            // 权限限制
            ->when(!user()->hasAnyAccess(['superuser', 'customer.view.all']), function (Builder $query) {
                $ids = user()->getCustomerViewUsersPermission();
                $query->where(function ($query) use ($ids) {
                    $query->whereIn('customer.ascription', $ids)
                        ->orWhereIn('customer.consultant', $ids);
                });
            })
            ->orderBy('customer.' . $sort, $order);

        // 定义子查询，选择尚未在目标分组中的客户
        $subQuery = $customerQuery->leftJoin('customer_group_details', function ($join) use ($customer_group_id) {
            $join->on('customer_group_details.customer_id', '=', 'customer.id')
                ->where('customer_group_details.customer_group_id', '=', $customer_group_id);
        })
            ->whereNull('customer_group_details.customer_id')
            ->select([
                'customer.id as customer_id',
                DB::raw("'{$customer_group_id}' as customer_group_id"),
                DB::raw("'{$now}' as created_at"),
                DB::raw("'{$now}' as updated_at"),
            ]);

        // 插入新的客户-分组关联
        DB::table('customer_group_details')->insertUsing(
            ['customer_id', 'customer_group_id', 'created_at', 'updated_at'],
            $subQuery
        );

        // 更新分组数量
        DB::table('customer_groups')
            ->where('id', $customer_group_id)
            ->update([
                'count' => DB::table('customer_group_details')->where('customer_group_id', $customer_group_id)->count()
            ]);
    }

    /**
     * 勾选移除分组
     * @return void
     */
    public function removeGroupByIds(): void
    {
        $ids               = $this->input('ids', []);
        $customer_group_id = $this->input('customer_group_id');
        $action            = addslashes(request()->route()->getActionName());
        $user_id           = user()->id;
        $now               = now()->toDateTimeString();

        // 定义子查询，获取需要删除的记录
        $entriesToDelete = DB::table('customer_group_details')
            ->select([
                'customer_group_details.customer_id',
                'customer_group_details.customer_group_id',
                DB::raw("'{$now}' as created_at"),
                DB::raw("'{$now}' as updated_at"),
            ])
            ->whereIn('customer_group_details.customer_id', $ids)
            ->where('customer_group_details.customer_group_id', $customer_group_id);

        // 写入日志
        DB::table('customer_log')->insertUsing(
            ['customer_id', 'action', 'user_id', 'logable_id', 'logable_type', 'original', 'dirty', 'created_at', 'updated_at'],
            DB::query()
                ->fromSub($entriesToDelete, 'sub')
                ->select([
                    'customer_id',
                    DB::raw("'{$action}' AS action"),
                    DB::raw("'{$user_id}' as user_id"),
                    'customer_group_id as logable_id',
                    DB::raw("'App\\\\Models\\\\CustomerGroup' AS logable_type"),
                    DB::raw('null AS original'),
                    DB::raw('null AS dirty'),
                    DB::raw("'{$now}' AS created_at"),
                    DB::raw("'{$now}' AS updated_at")
                ])
        );

        // 删除指定的客户分组关系
        DB::table('customer_group_details')
            ->whereIn('customer_id', $ids)
            ->where('customer_group_id', $customer_group_id)
            ->delete();
    }

    /**
     * 全选移除分组
     * @return void
     */
    public function removeGroupByAll(): void
    {
        $customer_group_id = $this->input('customer_group_id');
        $now               = now()->toDateTimeString();
        $sort              = $this->input('sort', 'created_at');
        $order             = $this->input('order', 'desc');
        $keyword           = $this->input('keyword');
        $group_id          = $this->input('group_id', 'all') === 'all' ? null : $this->input('group_id');
        $action            = addslashes(request()->route()->getActionName());
        $user_id           = user()->id;

        // Build the base customer query
        $customerQuery = Customer::query()
            ->select('customer.id')
            ->when($keyword, function (Builder $query) use ($keyword) {
                $query->where('customer.keyword', 'like', "%{$keyword}%");
            })
            ->when($group_id, function (Builder $query) use ($group_id) {
                $query->leftJoin('customer_group_details as cgd', 'cgd.customer_id', '=', 'customer.id')
                    ->where('cgd.customer_group_id', $group_id);
            })
            ->queryConditions('CustomerIndex')
            // Apply permission limitations
            ->when(!user()->hasAnyAccess(['superuser', 'customer.view.all']), function (Builder $query) {
                $ids = user()->getCustomerViewUsersPermission();
                $query->where(function ($query) use ($ids) {
                    $query->whereIn('customer.ascription', $ids)
                        ->orWhereIn('customer.consultant', $ids);
                });
            })
            ->orderBy('customer.' . $sort, $order);

        // Prepare the subquery to select customer_group_details to delete
        $subQuery = DB::table('customer_group_details')
            ->select([
                'customer_group_details.customer_id',
                'customer_group_details.customer_group_id',
                DB::raw("'{$now}' as created_at"),
                DB::raw("'{$now}' as updated_at")
            ])
            ->joinSub($customerQuery, 'customer', function ($join) {
                $join->on('customer_group_details.customer_id', '=', 'customer.id');
            })
            ->where('customer_group_details.customer_group_id', $customer_group_id);

        // Insert logs into customer_log
        DB::table('customer_log')->insertUsing(
            ['customer_id', 'action', 'user_id', 'logable_id', 'logable_type', 'original', 'dirty', 'created_at', 'updated_at'],
            DB::query()
                ->fromSub($subQuery, 'sub')
                ->select([
                    'customer_id',
                    DB::raw("'{$action}' AS action"),
                    DB::raw("'{$user_id}' as user_id"),
                    'customer_group_id as logable_id',
                    DB::raw("'App\\\\Models\\\\CustomerGroup' AS logable_type"),
                    DB::raw('null AS original'),
                    DB::raw('null AS dirty'),
                    DB::raw("'{$now}' AS created_at"),
                    DB::raw("'{$now}' AS updated_at")
                ])
        );

        // Delete from customer_group_details where customer_id matches and customer_group_id matches
        DB::table('customer_group_details')
            ->where('customer_group_id', $customer_group_id)
            ->whereExists(function ($query) use ($customerQuery) {
                $query->select(DB::raw(1))
                    ->fromSub($customerQuery, 'customer')
                    ->whereColumn('customer.id', 'customer_group_details.customer_id');
            })
            ->delete();
    }

    /**
     * 勾选更改分群
     * @return void
     */
    /**
     * 勾选更改分群
     * @return void
     */
    public function changeGroupByIds(): void
    {
        $ids               = $this->input('ids', []);
        $group_id          = $this->input('group_id'); // Current group ID
        $customer_group_id = $this->input('customer_group_id'); // New group ID
        $now               = now()->toDateTimeString();

        // 删除顾客旧的分组关系
        DB::table('customer_group_details')
            ->whereIn('customer_id', $ids)
            ->where('customer_group_id', $group_id)
            ->delete();

        // 插入新的客户分组关系
        // 定义子查询，选择尚未在目标分组中的客户
        $subQuery = DB::table('customer')
            ->select([
                'customer.id as customer_id',
                DB::raw("'{$customer_group_id}' as customer_group_id"),
                DB::raw("'{$now}' as created_at"),
                DB::raw("'{$now}' as updated_at"),
            ])
            ->leftJoin('customer_group_details', function ($join) use ($customer_group_id) {
                $join->on('customer_group_details.customer_id', '=', 'customer.id')
                    ->where('customer_group_details.customer_group_id', '=', $customer_group_id);
            })
            ->whereIn('customer.id', $ids)
            ->whereNull('customer_group_details.customer_id');

        // 插入到新的客户分组关系
        DB::table('customer_group_details')->insertUsing(
            ['customer_id', 'customer_group_id', 'created_at', 'updated_at'],
            $subQuery
        );
    }

    /**
     * 全选更改分群
     * @return void
     */
    /**
     * 全选更改分群
     * @return void
     */
    public function changeGroupByAll(): void
    {
        $customer_group_id = $this->input('customer_group_id'); // 目标分组ID
        $group_id          = $this->input('group_id'); // 当前分组ID
        $now               = now()->toDateTimeString();
        $sort              = $this->input('sort', 'created_at');
        $order             = $this->input('order', 'desc');
        $keyword           = $this->input('keyword');

        // 构建基础客户查询
        $customerQuery = Customer::query()
            ->select('customer.id')
            ->when($keyword, function (Builder $query) use ($keyword) {
                $query->where('customer.keyword', 'like', "%{$keyword}%");
            })
            // 关联当前分组
            ->leftJoin('customer_group_details as cgd', 'cgd.customer_id', '=', 'customer.id')
            ->where('cgd.customer_group_id', $group_id)
            ->queryConditions('CustomerIndex')
            // 权限限制
            ->when(!user()->hasAnyAccess(['superuser', 'customer.view.all']), function (Builder $query) {
                $ids = user()->getCustomerViewUsersPermission();
                $query->where(function ($query) use ($ids) {
                    $query->whereIn('customer.ascription', $ids)
                        ->orWhereIn('customer.consultant', $ids);
                });
            })
            ->orderBy('customer.' . $sort, $order);

        // 删除旧的分组关系
        DB::table('customer_group_details')
            ->where('customer_group_id', $group_id)
            ->whereExists(function ($query) use ($customerQuery) {
                $query->select(DB::raw(1))
                    ->fromSub($customerQuery, 'customer')
                    ->whereColumn('customer.id', 'customer_group_details.customer_id');
            })
            ->delete();

        // 构建插入新分组关系的子查询
        $insertQuery = $customerQuery->clone()->leftJoin('customer_group_details as new_cgd', function ($join) use ($customer_group_id) {
            $join->on('new_cgd.customer_id', '=', 'customer.id')
                ->where('new_cgd.customer_group_id', '=', $customer_group_id);
        })
            ->whereNull('new_cgd.customer_id')
            ->select([
                'customer.id as customer_id',
                DB::raw("'{$customer_group_id}' as customer_group_id"),
                DB::raw("'{$now}' as created_at"),
                DB::raw("'{$now}' as updated_at"),
            ]);

        // 插入新的分组关系
        DB::table('customer_group_details')->insertUsing(
            ['customer_id', 'customer_group_id', 'created_at', 'updated_at'],
            $insertQuery
        );
    }

    /**
     * 勾选部分顾客更新归属
     * @param string $field 字段
     * @return void
     */
    public function updateOwnershipByIds(string $field): void
    {
        $ids     = $this->input('ids', []);
        $users   = $this->input('users', []);
        $limit   = $this->input('limit', 0);
        $rules   = $this->input('distribution_rules');
        $count   = count($users);
        $action  = addslashes(request()->route()->getActionName());
        $user_id = user()->id;

        $subQuery = DB::table('customer')
            ->select('id')
            ->whereIn('id', $ids)
            ->when($limit, fn($query) => $query->limit($limit));

        // 分配给1个人(不需要考虑分配规则)
        if (count($users) == 1) {
            $subQuery->addSelect(DB::raw($users[0] . " as {$field}"));
        }

        // 分配给多个人 && 交替分配
        if (count($users) > 1 && $rules == 'alternation') {
            $selectRaw = 'CASE ';
            foreach ($users as $key => $user) {
                $selectRaw .= "WHEN MOD ( row_number() OVER ( ORDER BY id ), {$count} ) = {$key} THEN {$user} ";
            }
            $selectRaw .= "END AS {$field}";
            $subQuery->addSelect(DB::raw($selectRaw));
        }

        // 分配给多个人 && 平均分配
        if (count($users) > 1 && $rules == 'equally') {
            $total   = $limit ?: count($ids);
            $average = floor($total / $count);

            $selectRaw = 'CASE ';
            foreach ($users as $key => $user) {
                $number    = $average * ($key + 1);
                $selectRaw .= ($key < $count - 1) ? "WHEN row_number() OVER ( ORDER BY id ) <= {$number} THEN {$user} " : "else {$user} ";
            }
            $selectRaw .= "END AS {$field}";

            $subQuery->addSelect(DB::raw($selectRaw));
        }

        // 写入日志
        DB::table('customer_log')->insertUsing(
            ['customer_id', 'action', 'user_id', 'logable_id', 'logable_type', 'original', 'dirty', 'created_at', 'updated_at'],
            DB::table('customer')
                ->select([
                    "customer.id as customer_id",
                    DB::raw("'{$action}' AS action"),
                    DB::raw("'{$user_id}' as user_id"),
                    "customer.id as logable_id",
                    DB::raw("'App\\\\Models\\\\Customer' AS logable_type"),
                    DB::raw("JSON_OBJECT('{$field}', cy_customer.{$field}) AS original"),
                    DB::raw("JSON_OBJECT('{$field}', cy_c.{$field}) AS dirty"),
                    DB::raw('NOW() AS created_at'),
                    DB::raw('NOW() AS updated_at')
                ])
                ->joinSub($subQuery, 'c', function ($join) {
                    $join->on('customer.id', '=', 'c.id');
                })
        );

        // 修改归属关系
        DB::table('customer')
            ->joinSub($subQuery, 'c', function ($join) {
                $join->on('customer.id', '=', 'c.id');
            })
            ->update([
                "customer.{$field}" => DB::raw("cy_c.{$field}")
            ]);
    }

    /**
     * 全选顾客更新归属
     * @param string $field 字段
     * @return void
     */
    public function updateOwnershipByAll(string $field): void
    {
        $sort     = $this->input('sort', 'created_at');
        $order    = $this->input('order', 'desc');
        $users    = $this->input('users');
        $rules    = $this->input('distribution_rules');
        $limit    = $this->input('limit', 0);
        $keyword  = $this->input('keyword');
        $group_id = $this->input('group_id', 'all') === 'all' ? null : $this->input('group_id');
        $action   = addslashes(request()->route()->getActionName());
        $user_id  = user()->id;

        $subQuery = Customer::query()
            ->select('customer.id')
            ->when($keyword, fn(Builder $query) => $query->where('customer.keyword', 'like', "%{$keyword}%"))
            ->when($group_id, fn(Builder $query) => $query->leftJoin('customer_group_details', 'customer_group_details.customer_id', '=', 'customer.id')
                ->where('customer_group_details.customer_group_id', $group_id)
            )
            ->queryConditions('CustomerIndex')
            // 权限限制
            ->when(!user()->hasAnyAccess(['superuser', 'customer.view.all']), function (Builder $query) {
                $ids = user()->getCustomerViewUsersPermission();
                $query->where(function ($query) use ($ids) {
                    $query->whereIn('customer.ascription', $ids)->orWhereIn('customer.consultant', $ids);
                });
            })
            ->when($limit, fn(Builder $query) => $query->limit($limit))
            ->orderBy('customer.' . $sort, $order);

        // 分配给1个人(不需要考虑分配规则)
        if (count($users) == 1) {
            $subQuery->addSelect(DB::raw($users[0] . " as {$field}"));
        }

        // 分配给多个人 && 交替分配
        if (count($users) > 1 && $rules == 'alternation') {
            $selectRaw = 'CASE ';
            foreach ($users as $key => $user) {
                $selectRaw .= "WHEN MOD ( row_number() OVER ( ORDER BY id ), " . count($users) . " ) = {$key} THEN {$user} ";
            }
            $selectRaw .= "END AS {$field}";

            $subQuery->addSelect(DB::raw($selectRaw));
        }

        // 分配给多个人 && 平均分配
        if (count($users) > 1 && $rules == 'equally') {
            $total   = $limit ?: $subQuery->count();
            $average = floor($total / count($users));

            $selectRaw = 'CASE ';
            foreach ($users as $key => $user) {
                $number    = $average * ($key + 1);
                $selectRaw .= ($key < count($users) - 1) ? "WHEN row_number() OVER ( ORDER BY id ) <= {$number} THEN {$user} " : "else {$user} ";
            }
            $selectRaw .= "END AS {$field}";

            $subQuery->addSelect(DB::raw($selectRaw));
        }

        // 写入日志
        DB::table('customer_log')->insertUsing(
            ['customer_id', 'action', 'user_id', 'logable_id', 'logable_type', 'original', 'dirty', 'created_at', 'updated_at'],
            DB::table('customer')
                ->select([
                    "customer.id as customer_id",
                    DB::raw("'{$action}' AS action"),
                    DB::raw("'{$user_id}' as user_id"),
                    "customer.id as logable_id",
                    DB::raw("'App\\\\Models\\\\Customer' AS logable_type"),
                    DB::raw("JSON_OBJECT('{$field}', cy_customer.{$field}) AS original"),
                    DB::raw("JSON_OBJECT('{$field}', cy_c.{$field}) AS dirty"),
                    DB::raw('NOW() AS created_at'),
                    DB::raw('NOW() AS updated_at')
                ])
                ->joinSub($subQuery, 'c', function ($join) {
                    $join->on('customer.id', '=', 'c.id');
                })
        );

        // 修改归属关系
        DB::table('customer')
            ->joinSub($subQuery, 'c', function ($join) {
                $join->on('customer.id', '=', 'c.id');
            })
            ->update([
                "customer.{$field}" => DB::raw("cy_c.{$field}")
            ]);
    }

    /**
     * 短信批量发送验证规则
     * @return array
     */
    private function getSmsRules(): array
    {
        $ids   = $this->input('ids', []);
        $isall = $this->input('isall', false);
        $rules = [
            'isall'       => 'required|boolean',
            'template_id' => ['required', 'exists:sms_templates,id'],
        ];

        // 添加短信开启状态检查
        if (!parameter('cywebos_sms_enable')) {
            $rules['template_id'][] = function ($attribute, $value, $fail) {
                $fail('短信功能未开启，无法发送短信！');
            };
        }

        // 全选
        if ($isall) {
            $rules['filters']  = ['nullable', 'array', new SceneRule('CustomerIndex')];
            $rules['group_id'] = $this->input('group_id') === 'all' ? 'required|in:all' : 'required|exists:customer_groups,id';
        }

        // 指定ids
        if (!empty($ids)) {
            $rules['ids']   = 'required|array';
            $rules['ids.*'] = 'required|exists:customer,id';
        }

        return $rules;
    }

    /**
     * 短信批量发送验证消息
     * @return array
     */
    private function getSmsMessages(): array
    {
        return [
            'isall.required'       => '[选择全部]不能为空!',
            'isall.boolean'        => '[选择全部]必须为布尔值!',
            'template_id.required' => '[短信模板]不能为空!',
            'template_id.exists'   => '[短信模板]不存在!',
            'ids.required'         => '[客户ID]不能为空!',
            'ids.array'            => '[客户ID]必须为数组!',
            'ids.*.required'       => '[客户ID]不能为空!',
            'ids.*.exists'         => '[客户ID]不存在!',
            'group_id.required'    => '[分组ID]不能为空!',
            'group_id.exists'      => '[分组ID]不存在!',
            'group_id.in'          => '[分组ID]错误!',
        ];
    }

    /**
     * 勾选客户ID批量发送短信
     * @return void
     */
    /**
     * 勾选客户ID批量发送短信
     * @return void
     */
    public function sendSmsByIds(): void
    {
        $ids         = $this->input('ids', []);
        $template_id = $this->input('template_id');
        $channel     = parameter('cywebos_sms_default_gateway', 'aliyun');
        $now         = now()->toDateTimeString();
        $user_id     = user()->id;

        // 启用显示原始手机号
        CustomerPhone::$showOriginalPhone = true;

        // 使用子查询和窗口函数，为每个客户找出优先级最高的电话号码（relation_id 最小）
        $subQuery = CustomerPhone::query()
            ->select(['customer_id', 'phone'])
            ->selectRaw('ROW_NUMBER() OVER (PARTITION BY customer_id ORDER BY relation_id) as rn')
            ->whereIn('customer_id', $ids)
            ->whereNotNull('phone')
            ->where('phone', '!=', '');

        // 筛选出每个客户优先级最高的电话
        $customerPhones = CustomerPhone::query()
            ->fromSub($subQuery, 'cp')
            ->where('rn', 1)
            ->get();

        foreach ($customerPhones as $customerPhone) {
            $sms = Sms::create([
                'template_id' => $template_id,
                'phone'       => $customerPhone->phone,
                'content'     => '',
                'channel'     => $channel,
                'status'      => SmsStatus::PENDING,
                'user_id'     => $user_id,
                'scenario'    => 'customer',
                'scenario_id' => $customerPhone->customer_id,
                'created_at'  => $now,
                'updated_at'  => $now,
            ]);

            // 推送到异步队列
            SendSmsJob::dispatch($sms);
        }

        // 恢复隐藏手机号
        CustomerPhone::$showOriginalPhone = false;
    }

    /**
     * 全选批量发送短信
     * @return void
     */
    public function sendSmsByAll(): void
    {
        $hasPermission = !user()->hasAnyAccess(['superuser', 'customer.view.all']);
        $params        = [
            'template_id'    => $this->input('template_id'),
            'channel'        => parameter('cywebos_sms_default_gateway', 'aliyun'),
            'user_id'        => user()->id,
            'sort'           => $this->input('sort', 'created_at'),
            'order'          => $this->input('order', 'desc'),
            'keyword'        => $this->input('keyword'),
            'group_id'       => $this->input('group_id', 'all') === 'all' ? null : $this->input('group_id'),
            'has_permission' => $hasPermission,
            'permission_ids' => $hasPermission ? user()->getCustomerViewUsersPermission() : [],
        ];
        BatchSendSmsJob::dispatch($params);
    }
}
