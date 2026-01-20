<?php

namespace App\Http\Requests\Web;

use App\Rules\Web\SceneRule;
use Illuminate\Foundation\Http\FormRequest;

class ReportConsultantRequest extends FormRequest
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
            'order' => $this->getOrderRules(),
            default => []
        };
    }

    public function messages(): array
    {
        return match (request()->route()->getActionMethod()) {
            'order' => $this->getOrderMessages(),
            default => []
        };
    }

    private function getOrderRules(): array
    {
        return [
            'filters'      => [
                'nullable',
                'array',
                new SceneRule('ReportConsultantOrder'),
            ],
            'rows'         => 'required|integer|min:1|max:100',
            'page'         => 'required|integer|min:1',
            'sort'         => 'nullable|string',
            'order'        => 'nullable|string|in:asc,desc',
            'created_at'   => 'required|array|size:2',
            'created_at.0' => 'required|date',
            'created_at.1' => 'required|date|after_or_equal:created_at.0',
            'keyword'      => 'nullable|string|max:100',
        ];
    }

    private function getOrderMessages(): array
    {
        return [
            'filters.array'               => '筛选条件必须为数组',
            'rows.required'               => '每页条数不能为空',
            'rows.integer'                => '每页条数必须是整数',
            'rows.min'                    => '每页条数最少为1',
            'rows.max'                    => '每页条数最多为100',
            'page.required'               => '页码不能为空',
            'page.integer'                => '页码必须是整数',
            'page.min'                    => '页码至少为1',
            'sort.string'                 => '排序字段必须为字符串',
            'order.string'                => '排序方式必须为字符串',
            'order.in'                    => '排序方式只能为asc或desc',
            'created_at.required'         => '日期范围不能为空',
            'created_at.array'            => '日期范围必须为数组',
            'created_at.size'             => '日期范围必须包含开始和结束日期',
            'created_at.0.required'       => '开始日期不能为空',
            'created_at.0.date'           => '开始日期格式不正确',
            'created_at.1.required'       => '结束日期不能为空',
            'created_at.1.date'           => '结束日期格式不正确',
            'created_at.1.after_or_equal' => '结束日期不能早于开始日期',
            'keyword.string'              => '关键词必须是字符串',
            'keyword.max'                 => '关键词最多100个字符',
        ];
    }
}
