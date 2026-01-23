<?php

namespace App\Http\Requests\Api;

use App\Enums\FollowupStatus;
use Illuminate\Foundation\Http\FormRequest;

class FollowupRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return match (request()->route()->getActionMethod()) {
            'index' => $this->getIndexRules(),
            'info' => $this->getInfoRules(),
            'create' => $this->getCreateRules(),
            'execute' => $this->getExecuteRules(),
            default => []
        };
    }

    /**
     * Get the validation error messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return match (request()->route()->getActionMethod()) {
            'index' => $this->getIndexMessages(),
            'info' => $this->getInfoMessages(),
            'create' => $this->getCreateMessages(),
            'execute' => $this->getExecuteMessages(),
            default => []
        };
    }

    /**
     * Get form data for database operations.
     *
     * @return array
     */
    public function formData(): array
    {
        return match (request()->route()->getActionMethod()) {
            'create' => $this->getCreateFormData(),
            'execute', 'update' => $this->getExecuteFormData(),
            default => []
        };
    }

    /**
     * 回访列表验证规则
     */
    private function getIndexRules(): array
    {
        return [
            'date_start' => 'required|date_format:Y-m-d',
            'date_end'   => 'required|date_format:Y-m-d|after_or_equal:date_start',
            'type'       => 'nullable|array',
            'type.*'     => 'exists:followup_type,id',
            'status'     => 'nullable|integer|in:1,2',
            'keyword'    => 'nullable|string'
        ];
    }

    /**
     * 回访列表错误消息
     */
    private function getIndexMessages(): array
    {
        return [
            'date_start.required'     => '[开始日期]不能为空!',
            'date_start.date_format'  => '[开始日期]格式错误，必须为Y-m-d格式!',
            'date_end.required'       => '[结束日期]不能为空!',
            'date_end.date_format'    => '[结束日期]格式错误，必须为Y-m-d格式!',
            'date_end.after_or_equal' => '[结束日期]不能早于[开始日期]!',
            'type.array'              => '[回访类型]必须是数组!',
            'type.*.exists'           => '[回访类型]数据不存在!',
            'status.integer'          => '[回访状态]必须是数字类型!',
            'status.in'               => '[回访状态]必须是1或2!',
            'keyword.string'          => '[关键字]必须是字符串类型!'
        ];
    }

    /**
     * 回访信息验证规则
     */
    private function getInfoRules(): array
    {
        return [
            'id' => 'required|string|exists:followup'
        ];
    }

    /**
     * 回访信息错误消息
     */
    private function getInfoMessages(): array
    {
        return [
            'id.required' => 'id参数不能为空!',
            'id.string'   => 'id参数格式错误!',
            'id.exists'   => '数据不存在!',
        ];
    }

    /**
     * 创建回访验证规则
     */
    private function getCreateRules(): array
    {
        return [
            'customer_id'   => 'required|exists:customer,id',
            'title'         => 'required',
            'date'          => 'required|date_format:Y-m-d',
            'type'          => 'required|exists:followup_type,id',
            'tool'          => 'nullable|exists:followup_tool,id',
            'followup_user' => 'required|exists:users,id'
        ];
    }

    /**
     * 创建回访错误消息
     */
    private function getCreateMessages(): array
    {
        return [
            'customer_id.required'   => 'customer_id参数不能为空!',
            'customer_id.exists'     => '[顾客信息]没有找到!',
            'title.required'         => '[回访主题]不能为空!',
            'date.required'          => '[提醒日期]不能为空!',
            'date.date_format'       => '[提醒日期]格式错误!',
            'type.required'          => '[回访类型]不能为空!',
            'followup_user.required' => '[提醒人员]不能为空!',
        ];
    }

    /**
     * 创建回访表单数据
     */
    private function getCreateFormData(): array
    {
        return [
            'customer_id'   => $this->input('customer_id'),
            'type'          => $this->input('type'),
            'status'        => $this->input('remark') ? 2 : 1, // 回访状态
            'tool'          => $this->input('tool'),
            'title'         => $this->input('title'),
            'date'          => $this->input('date'),
            'time'          => $this->input('remark') ? date("Y-m-d H:i:s") : null,
            'remark'        => $this->input('remark') ?? null,
            'followup_user' => $this->input('followup_user'),
            'execute_user'  => $this->input('remark') ? user()->id : null,
            'user_id'       => user()->id,
        ];
    }

    /**
     * 执行回访验证规则
     */
    private function getExecuteRules(): array
    {
        return [
            'id'     => 'required|exists:followup',
            'date'   => 'required|date_format:Y-m-d',
            'title'  => 'required|string',
            'type'   => 'required|exists:followup_type,id',
            'tool'   => 'required|exists:followup_tool,id',
            'remark' => 'required|string',
        ];
    }

    /**
     * 执行回访错误消息
     */
    private function getExecuteMessages(): array
    {
        return [
            'id.required'      => '[回访ID]不能为空!',
            'id.exists'        => '[回访记录]不存在!',
            'date.required'    => '[回访日期]不能为空!',
            'date.date_format' => '[回访日期]格式错误，必须为Y-m-d格式!',
            'title.required'   => '[回访标题]不能为空!',
            'title.string'     => '[回访标题]必须是字符串!',
            'type.required'    => '[回访类型]不能为空!',
            'type.exists'      => '[回访类型]不存在!',
            'tool.required'    => '[回访工具]不能为空!',
            'tool.exists'      => '[回访工具]不存在!',
            'remark.required'  => '[回访备注]不能为空!',
            'remark.string'    => '[回访备注]必须是字符串!',
        ];
    }

    /**
     * 执行回访表单数据
     */
    private function getExecuteFormData(): array
    {
        return [
            'date'         => $this->input('date'),
            'title'        => $this->input('title'),
            'type'         => $this->input('type'),
            'tool'         => $this->input('tool'),
            'time'         => date("Y-m-d H:i:s"),
            'remark'       => $this->input('remark'),
            'execute_user' => user()->id,
            'status'       => FollowupStatus::COMPLETED,
        ];
    }
}
