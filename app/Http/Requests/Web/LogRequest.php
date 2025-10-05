<?php

namespace App\Http\Requests\Web;

use Illuminate\Foundation\Http\FormRequest;

class LogRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return match (request()->route()->getActionMethod()) {
            'login' => $this->getLoginRules(),
            'export' => $this->getExportRules(),
            'customer' => $this->getCustomerRules(),
            'phone' => $this->getPhoneRules(),
            default => []
        };
    }

    /**
     * Get custom validation messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return match (request()->route()->getActionMethod()) {
            'login' => $this->getLoginMessages(),
            'export' => $this->getExportMessages(),
            'customer' => $this->getCustomerMessages(),
            'phone' => $this->getPhoneMessages(),
            default => []
        };
    }

    /**
     * 登录日志验证规则
     */
    private function getLoginRules(): array
    {
        return [
            'created_at' => 'required|array|size:2',
            'user_id'    => 'nullable|integer|exists:users,id',
            'keyword'    => 'nullable|string|max:100',
            'rows'       => 'nullable|integer|min:1|max:100',
        ];
    }

    /**
     * 登录日志验证错误消息
     */
    private function getLoginMessages(): array
    {
        return [
            'created_at.required' => '[登录日期]不能为空',
            'created_at.array'    => '[登录日期]必须是数组',
            'created_at.size'     => '[登录日期]格式不正确',
            'user_id.integer'     => '[用户]必须是整数',
            'user_id.exists'      => '[用户]不存在',
            'keyword.string'      => '[关键字]必须是字符串',
            'keyword.max'         => '[关键字]不能超过100个字符',
            'rows.integer'        => '[每页数量]必须是整数',
            'rows.min'            => '[每页数量]不能小于1',
            'rows.max'            => '[每页数量]不能大于100',
        ];
    }

    /**
     * 顾客修改日志验证规则
     */
    private function getCustomerRules(): array
    {
        return [
            'created_at'  => 'required|array|size:2',
            'rows'        => 'nullable|integer|min:1|max:100',
            'customer_id' => 'nullable|string|exists:customer,id',
            'user_id'     => 'nullable|integer|exists:users,id',
            'action'      => 'nullable|string|max:250',
        ];
    }

    /**
     * 顾客修改日志验证错误消息
     */
    private function getCustomerMessages(): array
    {
        return [
            'created_at.required' => '[修改日期]不能为空',
            'created_at.array'    => '[修改日期]必须是数组',
            'created_at.size'     => '[修改日期]格式不正确',
            'rows.integer'        => '[每页数量]必须是整数',
            'rows.min'            => '[每页数量]不能小于1',
            'rows.max'            => '[每页数量]不能大于100',
            'customer_id.string'  => '[顾客信息]必须是整数',
            'customer_id.exists'  => '[顾客信息]不存在',
            'user_id.integer'     => '[操作人员]必须是整数',
            'user_id.exists'      => '[操作人员]不存在',
            'action.string'       => '[操作行为]必须是字符串',
            'action.max'          => '[操作行为]不能超过250个字符',
        ];
    }

    private function getExportRules(): array
    {
        return [
            'created_at' => 'required|array|size:2',
            'status'     => 'nullable|string|in:pending,processing,completed,failed',
            'user_id'    => 'nullable|integer|exists:users,id',
        ];
    }

    private function getExportMessages(): array
    {
        return [
            'created_at.required' => '[导出日期]不能为空',
            'created_at.array'    => '[导出日期]必须是数组',
            'created_at.size'     => '[导出日期]格式不正确',
            'status.string'       => '[任务状态]必须是字符串',
            'status.in'           => '[任务状态]值不正确',
            'user_id.integer'     => '[导出人员]必须是整数',
            'user_id.exists'      => '[导出人员]不存在',
        ];
    }

    /**
     * 号码查看记录验证规则
     */
    private function getPhoneRules(): array
    {
        return [
            'created_at'  => 'required|array|size:2',
            'rows'        => 'nullable|integer|min:1|max:100',
            'phone'       => 'nullable|string|max:50',
            'customer_id' => 'nullable|string|exists:customer,id',
            'user_id'     => 'nullable|integer|exists:users,id',
        ];
    }

    /**
     * 号码查看记录验证错误消息
     */
    private function getPhoneMessages(): array
    {
        return [
            'created_at.required' => '[查看日期]不能为空',
            'created_at.array'    => '[查看日期]必须是数组',
            'created_at.size'     => '[查看日期]格式不正确',
            'rows.integer'        => '[每页数量]必须是整数',
            'rows.min'            => '[每页数量]不能小于1',
            'rows.max'            => '[每页数量]不能大于100',
            'phone.string'        => '[电话号码]必须是字符串',
            'phone.max'           => '[电话号码]不能超过50个字符',
            'customer_id.string'  => '[顾客信息]必须是字符串',
            'customer_id.exists'  => '[顾客信息]不存在',
            'user_id.integer'     => '[查看人员]必须是整数',
            'user_id.exists'      => '[查看人员]不存在',
        ];
    }
}
