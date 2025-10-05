<?php

namespace App\Http\Requests\Web;

use App\Helpers\ParseCdpField;
use Illuminate\Support\Facades\DB;
use App\Rules\GoogleAuthenticatorRule;
use App\Jobs\CustomerGroupQueryImportJob;
use Illuminate\Foundation\Http\FormRequest;

class CustomerGroupRequest extends FormRequest
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

    public function rules(): array
    {
        return match (request()->route()->getActionMethod()) {
            'addCategory' => $this->getAddCategoryRules(),
            'updateCategory' => $this->getUpdateCategoryRules(),
            'removeCategory' => $this->getRemoveCategoryRules(),
            'create' => $this->getCreateRules(),
            'update' => $this->getUpdateRules(),
            'compute' => $this->getComputeRules(),
            'remove' => $this->getRemoveRules(),
            'preview', 'copy' => $this->getInfoRules(),
            'import' => $this->getImportRules(),
            'removeCustomer' => $this->getRemoveCustomerRules(),
            'swapCategory' => $this->getSwapCategoryRules(),
            default => [],
        };
    }

    public function messages(): array
    {
        return match (request()->route()->getActionMethod()) {
            'addCategory' => $this->getAddCategoryMessages(),
            'updateCategory' => $this->getUpdateCategoryMessages(),
            'removeCategory' => $this->getRemoveCategoryMessages(),
            'create' => $this->getCreateMessages(),
            'update' => $this->getUpdateMessages(),
            'remove', 'compute', 'preview' => $this->getInfoMessages(),
            'import' => $this->getImportMessages(),
            'removeCustomer' => $this->getRemoveCustomerMessages(),
            'swapCategory' => $this->getSwapCategoryMessages(),
            default => [],
        };
    }

    /**
     * 静态分群删除顾客验证规则
     * @return string[]
     */
    protected function getRemoveCustomerRules(): array
    {
        return [
            'customer_group_id' => 'required|integer|exists:customer_groups,id',
            'ids'               => 'required|array',
            'ids.*'             => 'required|string|exists:customer_group_details,customer_id,customer_group_id,' . $this->input('customer_group_id'),
        ];
    }

    protected function getSwapCategoryRules(): array
    {
        return [
            'id1' => 'required|integer|exists:customer_group_categories,id',
            'id2' => 'required|integer|exists:customer_group_categories,id',
        ];
    }

    protected function getAddCategoryRules(): array
    {
        return [
            'name'        => 'required|string|max:255',
            'scope'       => 'required|in:all,departments,users',
            'scope_value' => [
                'nullable',
                'required_if:scope,departments,users',
                function ($attribute, $value, $fail) {
                    if ($this->input('scope') === 'departments' && !is_array($value)) {
                        $fail('部门ID必须为数组');
                        return;
                    }
                    if ($this->input('scope') === 'users' && !is_array($value)) {
                        $fail('用户ID必须为数组');
                        return;
                    }

                    // 检查部门是否存在
                    if ($this->input('scope') === 'departments') {
                        $exists = DB::table('department')->whereIn('id', $value)->count();
                        if ($exists !== count($value)) {
                            $fail('部分部门ID不存在');
                            return;
                        }
                    }

                    // 检查用户是否存在
                    if ($this->input('scope') === 'users') {
                        $exists = DB::table('users')->whereIn('id', $value)->count();
                        if ($exists !== count($value)) {
                            $fail('部分用户ID不存在');
                            return;
                        }
                    }
                }
            ],
        ];
    }

    protected function getAddCategoryMessages(): array
    {
        return [
            'name.required'           => '分类名称不能为空',
            'name.string'             => '分类名称必须为字符串',
            'name.max'                => '分类名称最大长度为255',
            'scope.required'          => '可见范围不能为空',
            'scope.in'                => '可见范围必须为all、departments或users',
            'scope_value.required_if' => '可见范围值不能为空',
        ];
    }

    protected function getUpdateCategoryRules(): array
    {
        return [
            'id'          => 'required|integer|exists:customer_group_categories,id',
            'name'        => 'required|string|max:255',
            'scope'       => 'required|in:all,departments,users',
            'scope_value' => [
                'nullable',
                'required_if:scope,departments,users',
                function ($attribute, $value, $fail) {
                    if ($this->input('scope') === 'departments' && !is_array($value)) {
                        $fail('部门ID必须为数组');
                        return;
                    }
                    if ($this->input('scope') === 'users' && !is_array($value)) {
                        $fail('用户ID必须为数组');
                        return;
                    }

                    // 检查部门是否存在
                    if ($this->input('scope') === 'departments') {
                        $exists = DB::table('department')->whereIn('id', $value)->count();
                        if ($exists !== count($value)) {
                            $fail('部分部门ID不存在');
                            return;
                        }
                    }

                    // 检查用户是否存在
                    if ($this->input('scope') === 'users') {
                        $exists = DB::table('users')->whereIn('id', $value)->count();
                        if ($exists !== count($value)) {
                            $fail('部分用户ID不存在');
                            return;
                        }
                    }
                }
            ],
        ];
    }

    protected function getUpdateCategoryMessages(): array
    {
        return [
            'id.required'             => '分类ID不能为空',
            'id.integer'              => '分类ID必须为整数',
            'id.exists'               => '分类ID不存在',
            'name.required'           => '分类名称不能为空',
            'name.string'             => '分类名称必须为字符串',
            'name.max'                => '分类名称最大长度为255',
            'scope.required'          => '可见范围不能为空',
            'scope.in'                => '可见范围必须为all、departments或users',
            'scope_value.required_if' => '可见范围值不能为空',
        ];
    }

    protected function getRemoveCategoryRules(): array
    {
        return [
            'id' => [
                'required',
                'integer',
                'exists:customer_group_categories,id',
                function ($attribute, $value, $fail) {
                    if (DB::table('customer_groups')->where('category_id', $value)->count()) {
                        $fail('该分类下有分群数据，不能删除');
                    }
                },
            ],
        ];
    }

    protected function getRemoveCategoryMessages(): array
    {
        return [
            'id.required' => '分类ID不能为空',
            'id.integer'  => '分类ID必须为整数',
            'id.exists'   => '分类ID不存在',
        ];
    }

    /**
     * 删除静态分群顾客验证消息
     * @return string[]
     */
    protected function getRemoveCustomerMessages(): array
    {
        return [
            'customer_group_id.required' => '分群ID不能为空',
            'customer_group_id.integer'  => '分群ID必须为整数',
            'customer_group_id.exists'   => '分群ID不存在',
            'ids.required'               => '顾客ID不能为空',
            'ids.array'                  => '顾客ID必须为数组',
            'ids.*.required'             => '顾客ID不能为空',
            'ids.*.string'               => '顾客ID必须为字符串',
            'ids.*.exists'               => '顾客ID不存在',
        ];
    }

    protected function getSwapCategoryMessages(): array
    {
        return [
            'id1.required' => '分类ID不能为空',
            'id1.integer'  => '分类ID必须为整数',
            'id1.exists'   => '分类ID不存在',
            'id2.required' => '分类ID不能为空',
            'id2.integer'  => '分类ID必须为整数',
            'id2.exists'   => '分类ID不存在',
        ];
    }

    /**
     * 客户分群表单数据
     * @return array
     */
    public function formData(): array
    {
        $data = [
            'name'           => $this->input('name'),
            'type'           => $this->input('type'),
            'sql'            => null,
            'remark'         => $this->input('remark'),
            'category_id'    => $this->input('category_id'),
            'create_user_id' => user()->id,
            'filter_rule'    => null,
            'exclude_rule'   => null,
        ];

        // 动态分群
        if ($data['type'] === 'dynamic') {
            $parser       = new ParseCdpField();
            $filter_rule  = $this->input('filter_rule');
            $exclude_rule = $this->input('exclude_rule');

            // 拼接参数
            $data['sql']          = $parser->filter($filter_rule)->exclude($exclude_rule)->getSql();
            $data['filter_rule']  = $filter_rule;
            $data['exclude_rule'] = $exclude_rule;
        }

        // SQL分群
        if ($data['type'] === 'sql') {
            $data['sql'] = $this->input('sql');
        }

        return $data;
    }

    /**
     * 在线导入客户数据
     * @param int $customer_group_id
     * @return void
     */
    public function importByOnline(int $customer_group_id): void
    {
        DB::table('customer_group_details')->insertUsing(
            [
                'customer_id',
                'customer_group_id',
                'created_at',
                'updated_at'
            ],
            DB::table('customer')
                ->select([
                    'customer.id',
                    DB::raw($customer_group_id),
                    DB::raw('NOW()'),
                    DB::raw('NOW()'),
                ])
                ->whereIn('customer.idcard', $this->input('rows', []))
                ->whereNotExists(function ($query) use ($customer_group_id) {
                    $query->select(DB::raw(1))
                        ->from('customer_group_details')
                        ->whereColumn('customer_group_details.customer_id', 'customer.id')
                        ->where('customer_group_details.customer_group_id', $customer_group_id);
                })
        );
        // 更新分群人数
        DB::table('customer_groups')->where('id', $customer_group_id)->update([
            'count' => DB::table('customer_group_details')->where('customer_group_id', $customer_group_id)->count()
        ]);
    }

    /**
     * 查询导入
     * @param int $customer_group_id
     * @return void
     */
    public function importByQuery(int $customer_group_id): void
    {
        $parser       = new ParseCdpField();
        $filter_rule  = $this->input('filter_rule');
        $exclude_rule = $this->input('exclude_rule');
        $sql          = $parser->filter($filter_rule)->exclude($exclude_rule)->getSql();

        // 异步查询导入
        dispatch(new CustomerGroupQueryImportJob(tenant()->id, $customer_group_id, $sql));
    }

    /**
     * 单个导入顾客
     * @param int $customer_group_id
     * @return void
     */
    public function importBySingle(int $customer_group_id): void
    {
        $exists = DB::table('customer_group_details')
            ->where('customer_group_id', $customer_group_id)
            ->where('customer_id', $this->input('customer_id'))
            ->exists();
        if (!$exists) {
            DB::table('customer_group_details')->insert([
                'customer_id'       => $this->input('customer_id'),
                'customer_group_id' => $customer_group_id,
                'created_at'        => now(),
                'updated_at'        => now(),
            ]);
            // 更新分群人数
            DB::table('customer_groups')->where('id', $customer_group_id)->update([
                'count' => DB::table('customer_group_details')->where('customer_group_id', $customer_group_id)->count()
            ]);
        }
    }

    /**
     * 文件导入
     * @param int $customer_group_id
     * @return void
     */
    public function importByFile(int $customer_group_id): void
    {

    }

    private function getCreateRules(): array
    {
        $rules = [
            'name'        => 'required|string|max:255',
            'type'        => 'required|in:dynamic,static,sql',
            'remark'      => 'nullable|string|max:255',
            'category_id' => 'required|integer|exists:customer_group_categories,id',
        ];

        // 动态分群验证
        if ($this->input('type') === 'dynamic') {
            $rules['filter_rule']  = 'nullable|array';
            $rules['exclude_rule'] = 'nullable|array';
        }

        // SQL分群验证
        if ($this->input('type') === 'sql') {
            $rules['sql'] = 'required|string';

            // 开启2FA验证
            if (admin_parameter('google2fa')) {
                $rules['code'] = [
                    new GoogleAuthenticatorRule(admin_parameter('google2fa'))
                ];
            }
        }

        return $rules;
    }

    private function getCreateMessages(): array
    {
        return [
            'name.required'        => '[分群名称]不能为空',
            'name.string'          => '[分群名称]必须为字符串',
            'name.max'             => '[分群名称]最大长度为255',
            'type.required'        => '[分群类型]不能为空',
            'type.in'              => '[分群类型]必须为dynamic或static',
            'remark.string'        => '[分群备注]必须为字符串',
            'remark.max'           => '[分群备注]最大长度为255',
            'category_id.required' => '[分类ID]不能为空',
            'category_id.integer'  => '[分类ID]必须为整数',
            'category_id.exists'   => '[分类ID]不存在',
            'filter_rule.array'    => '[筛选规则]必须为数组',
            'exclude_rule.array'   => '[排除规则]必须为数组',
            'sql.required'         => '[SQL代码]不能为空',
            'code.required_if'     => '[动态口令]不能为空',
        ];
    }

    private function getUpdateRules(): array
    {
        $rules = [
            'id'          => 'required|integer|exists:customer_groups,id',
            'name'        => 'required|string|max:255',
            'type'        => 'required|in:dynamic,static,sql',
            'remark'      => 'nullable|string|max:255',
            'category_id' => 'required|integer|exists:customer_group_categories,id',
        ];

        // 动态分群验证
        if ($this->input('type') === 'dynamic') {
            $rules['filter_rule']  = 'nullable|array';
            $rules['exclude_rule'] = 'nullable|array';
        }

        // SQL分群验证
        if ($this->input('type') === 'sql') {
            $rules['sql'] = 'required|string';

            // 开启2FA验证
            if (admin_parameter('google2fa')) {
                $rules['code'] = [
                    new GoogleAuthenticatorRule(admin_parameter('google2fa'))
                ];
            }
        }

        return $rules;
    }

    private function getUpdateMessages(): array
    {
        return [
            'id.required'          => '[分群ID]不能为空',
            'id.integer'           => '[分群ID]必须为整数',
            'id.exists'            => '[分群ID]不存在',
            'name.required'        => '[分群名称]不能为空',
            'name.string'          => '[分群名称]必须为字符串',
            'name.max'             => '[分群名称]最大长度为255',
            'type.required'        => '[分群类型]不能为空',
            'type.in'              => '[分群类型]必须为dynamic或static',
            'remark.string'        => '[分群备注]必须为字符串',
            'remark.max'           => '[分群备注]最大长度为255',
            'category_id.required' => '[分类ID]不能为空',
            'category_id.integer'  => '[分类ID]必须为整数',
            'category_id.exists'   => '[分类ID]不存在',
            'filter_rule.array'    => '[筛选规则]必须为数组',
            'exclude_rule.array'   => '[排除规则]必须为数组',
            'sql.required'         => '[SQL代码]不能为空',
            'code.required_if'     => '[动态口令]不能为空',
        ];
    }

    private function getComputeRules(): array
    {
        return [
            'id' => [
                'required',
                'integer',
                'exists:customer_groups,id',
                function ($attribute, $value, $fail) {
                    if (DB::table('customer_groups')->where('id', $value)->where('processing', 1)->count()) {
                        $fail('客户分群正在计算中!');
                        return;
                    }
                    if (DB::table('customer_groups')->where('id', $value)->where('type', 'static')->count()) {
                        $fail('静态分群不允许计算!');
                    }
                },
            ]
        ];
    }

    private function getRemoveRules(): array
    {
        return [
            'id' => [
                'required',
                'integer',
                'exists:customer_groups,id',
                function ($attribute, $value, $fail) {
                    if (DB::table('customer_groups')->where('id', $value)->where('processing', 1)->count()) {
                        $fail('客户分群正在计算中!');
                    }
                },
            ],
        ];
    }

    private function getInfoRules(): array
    {
        return [
            'id' => 'required|integer|exists:customer_groups,id',
        ];
    }

    private function getInfoMessages(): array
    {
        return [
            'id.required' => '分群ID不能为空',
            'id.integer'  => '分群ID必须为整数',
            'id.exists'   => '分群ID不存在',
        ];
    }

    private function getImportRules(): array
    {
        return [
            'type'              => 'required|in:single,online,file,query',
            'customer_group_id' => 'required|integer|exists:customer_groups,id',
            'rows'              => 'nullable|required_if:type,online|array',
            'file'              => 'nullable|required_if:type,file|file',
            'filter_rule'       => 'nullable|required_if:type,query|array',
            'customer_id'       => 'nullable|required_if:type,single|exists:customer,id',
        ];
    }

    private function getImportMessages(): array
    {
        return [
            'type.required'      => '[导入类型]不能为空',
            'type.in'            => '[导入类型]必须为single、online、file或query',
            'rows.array'         => '[在线导入数据]必须为数组',
            'file.file'          => '[文件导入数据]必须为文件',
            'filter_rule.array'  => '[查询导入数据]必须为数组',
            'customer_id.exists' => '[添加顾客]不存在',
        ];
    }
}
