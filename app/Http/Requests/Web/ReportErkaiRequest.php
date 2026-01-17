<?php

namespace App\Http\Requests\Web;

use App\Rules\Web\SceneRule;
use Illuminate\Foundation\Http\FormRequest;

class ReportErkaiRequest extends FormRequest
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
            default => [],
            'detail' => $this->getDetailRules(),
        };
    }

    public function messages(): array
    {
        return match (request()->route()->getActionMethod()) {
            default => [],
            'detail' => $this->getDetailMessages(),
        };
    }

    private function getDetailRules(): array
    {
        return [
            'filters'      => [
                'nullable',
                'array',
                new SceneRule('ReportErkaiDetail'),
            ],
            'rows'         => 'required|integer|min:1',
            'page'         => 'required|integer|min:1',
            'sort'         => 'nullable|string',
            'order'        => 'nullable|string|in:asc,desc',
            'created_at'   => 'required|array|size:2',
            'created_at.0' => 'required|date',
            'created_at.1' => 'required|date|after_or_equal:created_at.0',
            'keyword'      => 'nullable|string',
        ];
    }

    private function getDetailMessages(): array
    {
        return [
            'filters.array'                 => '筛选条件必须为数组',
            'rows.required'                 => '每页数量不能为空',
            'rows.integer'                  => '每页数量必须为整数',
            'rows.min'                      => '每页数量不能小于1',
            'page.required'                 => '页码不能为空',
            'page.integer'                  => '页码必须为整数',
            'page.min'                      => '页码不能小于1',
            'sort.string'                   => '排序字段必须为字符串',
            'order.string'                  => '排序方式必须为字符串',
            'order.in'                      => '排序方式只能为asc或desc',
            'created_at.required'           => '日期范围不能为空',
            'created_at.array'              => '日期范围必须为数组',
            'created_at.size'               => '日期范围必须包含开始和结束日期',
            'created_at.0.required'         => '开始日期不能为空',
            'created_at.0.date'             => '开始日期格式不正确',
            'created_at.1.required'         => '结束日期不能为空',
            'created_at.1.date'             => '结束日期格式不正确',
            'created_at.1.after_or_equal'   => '结束日期不能早于开始日期',
            'keyword.string'                => '关键词必须为字符串',
        ];
    }
}
