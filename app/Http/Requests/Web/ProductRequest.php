<?php

namespace App\Http\Requests\Web;

use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Http\FormRequest;

class ProductRequest extends FormRequest
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
            'manage' => $this->getManageRules(),
            'create' => $this->getCreateRules(),
            'update' => $this->getUpdateRules(),
            'import' => $this->getImportRules(),
            'remove' => $this->getRemoveRules(),
            'batch' => $this->getBatchRules(),
            'enable' => $this->getEnableRules(),
            'disable' => $this->getDisableRules(),
            default => []
        };
    }

    public function messages(): array
    {
        return match (request()->route()->getActionMethod()) {
            'manage' => $this->getManageMessages(),
            'create' => $this->getCreateMessages(),
            'update' => $this->getUpdateMessages(),
            'import' => $this->getImportMessages(),
            'remove' => $this->getRemoveMessages(),
            'batch' => $this->getBatchMessages(),
            'enable' => $this->getEnableMessages(),
            'disable' => $this->getDisableMessages(),
            default => []
        };
    }

    /**
     * 表单数据
     * @return array
     */
    public function formData(): array
    {
        return [
            'name'                => $this->input('name'),
            'print_name'          => $this->input('print_name'),
            'type_id'             => $this->input('type_id'),
            'price'               => $this->input('price'),
            'sales_price'         => $this->input('sales_price'),
            'expiration'          => $this->input('expiration', 0),
            'specs'               => $this->input('specs'),
            'times'               => $this->input('times'),
            'department_id'       => $this->input('department_id'),
            'deduct_department'   => $this->input('deduct_department'),
            'deduct'              => $this->input('deduct', 0),
            'commission'          => $this->input('commission', 0),
            'integral'            => $this->input('integral', 0),
            'expense_category_id' => $this->input('expense_category_id', 1),
            'successful'          => $this->input('successful'),
            'remark'              => $this->input('remark'),
        ];
    }

    public function batchForm(): array
    {
        $data = [];

        if ($this->has('disabled')) {
            $data['disabled'] = !$this->input('disabled');
        }

        if ($this->has('deduct')) {
            $data['deduct'] = $this->input('deduct');
        }

        if ($this->has('commission')) {
            $data['commission'] = $this->input('commission');
        }

        if ($this->has('integral')) {
            $data['integral'] = $this->input('integral');
        }

        if ($this->has('successful')) {
            $data['successful'] = $this->input('successful');
        }

        if ($this->has('department_id')) {
            $data['department_id'] = $this->input('department_id');
        }

        if ($this->has('deduct_department')) {
            $data['deduct_department'] = $this->input('deduct_department');
        }

        if ($this->has('expense_category_id')) {
            $data['expense_category_id'] = $this->input('expense_category_id');
        }

        if ($this->input('type_id')) {
            $data['type_id'] = $this->input('type_id');
        }

        if ($this->input('remark')) {
            $data['remark'] = $this->input('remark');
        }

        return $data;
    }

    /**
     * 获取manage方法的验证规则
     *
     * @return array
     */
    private function getManageRules(): array
    {
        return [
            'type_id' => 'required|integer|exists:product_type,id',
        ];
    }

    /**
     * 获取manage方法的错误消息
     *
     * @return array
     */
    private function getManageMessages(): array
    {
        return [
            'type_id.required' => '缺少type_id参数',
            'type_id.integer'  => 'type_id必须是数字',
            'type_id.exists'   => '没有找到分类数据'
        ];
    }

    /**
     * 获取create方法的验证规则
     *
     * @return array
     */
    private function getCreateRules(): array
    {
        return [
            'name'                => 'required',
            'type_id'             => 'required|integer|exists:product_type,id',
            'price'               => 'required',
            'sales_price'         => 'required',
            'times'               => 'required',
            'expiration'          => 'required',
            'expense_category_id' => 'required|exists:expense_category,id',
            'successful'          => 'required|boolean'
        ];
    }

    /**
     * 获取create方法的错误消息
     *
     * @return array
     */
    private function getCreateMessages(): array
    {
        return [
            'name.required'                => '[项目名称]不能为空!',
            'type_id.required'             => '[项目分类]不能为空!',
            'type_id.integer'              => '[项目分类]必须是数字',
            'type_id.exists'               => '[项目分类]不存在!',
            'price.required'               => '[项目原价]不能为空!',
            'sales_price.required'         => '[执行价格]不能为空!',
            'times.required'               => '[使用次数]不能为空!',
            'expiration.required'          => '[使用期限]不能为空!',
            'expense_category_id.required' => '[费用类别]不能为空!',
            'expense_category_id.exists'   => '[费用类别]不能不存在!',
            'successful.required'          => '[统计成交]不能为空!',
        ];
    }

    /**
     * 获取update方法的验证规则
     *
     * @return array
     */
    private function getUpdateRules(): array
    {
        return [
            'id'                  => 'required|exists:product',
            'name'                => 'required',
            'type_id'             => 'required|integer|exists:product_type,id',
            'price'               => 'required',
            'sales_price'         => 'required',
            'expiration'          => 'required',
            'expense_category_id' => 'required|exists:expense_category,id'
        ];
    }

    /**
     * 获取update方法的错误消息
     *
     * @return array
     */
    private function getUpdateMessages(): array
    {
        return [
            'id.required'                  => '[项目ID]不能为空!',
            'id.exists'                    => '[项目ID]不存在!',
            'name.required'                => '[项目名称]不能为空!',
            'type_id.required'             => '[项目分类]不能为空!',
            'type_id.integer'              => '[项目分类]必须是数字',
            'type_id.exists'               => '[项目分类]不存在!',
            'price.required'               => '[项目原价]不能为空!',
            'sales_price.required'         => '[执行价格]不能为空!',
            'expiration.required'          => '[使用期限]不能为空!',
            'expense_category_id.required' => '[费用类别]不能为空!',
            'expense_category_id.exists'   => '[费用类别]不能不存在!',
        ];
    }

    /**
     * 获取import方法的验证规则
     *
     * @return array
     */
    private function getImportRules(): array
    {
        return [
            'excel' => 'required|mimes:xls,xlsx'
        ];
    }

    /**
     * 获取import方法的错误消息
     *
     * @return array
     */
    private function getImportMessages(): array
    {
        return [
            'excel.required' => '请选择上传的文件',
            'excel.mimes'    => '文件格式错误',
        ];
    }

    /**
     * 获取remove方法的验证规则
     *
     * @return array
     */
    private function getRemoveRules(): array
    {
        return [
            'id' => [
                'required',
                'array',
                function ($attribute, $value, $fail) {
                    if (in_array(1, $value) || in_array(2, $value)) {
                        $fail('系统默认项目不能删除');
                        return;
                    }
                    if (DB::table('reception_order')->where('product_id', $value)->count()) {
                        $fail('[现场开单]项目已经被使用，不能删除');
                        return;
                    }
                    if (DB::table('customer_product')->where('product_id', $value)->count()) {
                        $fail('[顾客成交项目]已经被使用，不能删除');
                        return;
                    }
                    if (DB::table('cashier_detail')->where('product_id', $value)->count()) {
                        $fail('[营收明细]已经被使用，不能删除');
                        return;
                    }
                }
            ]
        ];
    }

    /**
     * 获取remove方法的错误消息
     *
     * @return array
     */
    private function getRemoveMessages(): array
    {
        return [
            'id.required' => '请选择要删除的项目',
            'id.array'    => '请选择要删除的项目',
        ];
    }

    /**
     * 获取batch方法的验证规则
     *
     * @return array
     */
    private function getBatchRules(): array
    {
        return [
            'ids'                 => 'required|array',
            'disabled'            => 'nullable|boolean',
            'deduct'              => 'nullable|boolean',
            'commission'          => 'nullable|boolean',
            'integral'            => 'nullable|boolean',
            'successful'          => 'nullable|boolean',
            'department_id'       => 'nullable|exists:department,id',
            'deduct_department'   => 'nullable|exists:department,id',
            'expense_category_id' => 'nullable|exists:expense_category,id',
            'type_id'             => 'nullable|numeric|exists:product_type,id',
            'remark'              => 'nullable|string|max:255'
        ];
    }

    /**
     * 获取batch方法的错误消息
     *
     * @return array
     */
    private function getBatchMessages(): array
    {
        return [
            'ids.required'               => '请选择要批量操作的项目',
            'ids.array'                  => '请选择要批量操作的项目',
            'disabled.boolean'           => '禁用状态必须是布尔值',
            'deduct.boolean'             => '划扣状态必须是布尔值',
            'commission.boolean'         => '提成状态必须是布尔值',
            'integral.boolean'           => '积分状态必须是布尔值',
            'successful.boolean'         => '统计成交必须是布尔值',
            'department_id.exists'       => '结算科室不存在!',
            'deduct_department.exists'   => '划扣科室不存在!',
            'expense_category_id.exists' => '费用类别不存在!',
            'type_id.numeric'            => '项目分类必须是数字',
            'type_id.exists'             => '项目分类不存在!',
            'remark.string'              => '备注必须是字符串',
        ];
    }

    /**
     * 获取enable方法的验证规则
     *
     * @return array
     */
    private function getEnableRules(): array
    {
        return [
            'id' => 'required|integer|exists:product,id'
        ];
    }

    /**
     * 获取enable方法的错误消息
     *
     * @return array
     */
    private function getEnableMessages(): array
    {
        return [
            'id.required' => '缺少id参数',
            'id.integer'  => 'id必须是数字',
            'id.exists'   => '收费项目不存在'
        ];
    }

    /**
     * 获取disable方法的验证规则
     *
     * @return array
     */
    private function getDisableRules(): array
    {
        return [
            'id' => 'required|integer|exists:product,id'
        ];
    }

    /**
     * 获取disable方法的错误消息
     *
     * @return array
     */
    private function getDisableMessages(): array
    {
        return [
            'id.required' => '缺少id参数',
            'id.integer'  => 'id必须是数字',
            'id.exists'   => '收费项目不存在'
        ];
    }
}
