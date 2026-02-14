<?php

namespace App\Http\Requests\Web;

use Illuminate\Foundation\Http\FormRequest;

class ReportFollowupRequest extends FormRequest
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
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return match (request()->route()->getActionMethod()) {
            'statistics' => $this->getStatisticsRules(),
            default => [],
        };
    }

    /**
     * 获取验证规则的错误消息
     */
    public function messages(): array
    {
        return match (request()->route()->getActionMethod()) {
            'statistics' => $this->getStatisticsMessages(),
            default => [],
        };
    }

    /**
     * 回访统计验证规则
     */
    private function getStatisticsRules(): array
    {
        return [
            'date' => [
                'required',
                'array',
                'size:2',
            ],
            'date.0' => [
                'required',
                'date',
            ],
            'date.1' => [
                'required',
                'date',
                'after_or_equal:date.0',
            ],
            'user_id' => [
                'nullable',
                'integer',
                'exists:users,id',
            ],
            'department_id' => [
                'nullable',
                'integer',
                'exists:department,id',
            ],
            'rows' => [
                'nullable',
                'integer',
                'min:1',
                'max:100',
            ],
            'sort' => [
                'nullable',
                'string',
            ],
            'order' => [
                'nullable',
                'string',
                'in:asc,desc',
            ],
        ];
    }

    /**
     * 回访统计验证错误消息
     */
    private function getStatisticsMessages(): array
    {
        return [
            'date.required' => '日期范围不能为空',
            'date.array' => '日期范围必须是数组格式',
            'date.size' => '日期范围必须包含开始和结束日期',
            'date.0.required' => '开始日期不能为空',
            'date.0.date' => '开始日期格式不正确',
            'date.1.required' => '结束日期不能为空',
            'date.1.date' => '结束日期格式不正确',
            'date.1.after_or_equal' => '结束日期必须大于等于开始日期',
            'user_id.integer' => '员工ID必须为整数',
            'user_id.exists' => '所选员工不存在',
            'department_id.integer' => '部门ID必须为整数',
            'department_id.exists' => '所选部门不存在',
            'rows.integer' => '每页条数必须为整数',
            'rows.min' => '每页条数至少为1条',
            'rows.max' => '每页条数最多为100条',
            'order.in' => '排序方向必须为asc或desc',
        ];
    }
}
