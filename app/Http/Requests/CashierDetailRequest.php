<?php

namespace App\Http\Requests;

use App\Rules\Web\SceneRule;
use Illuminate\Foundation\Http\FormRequest;

class CashierDetailRequest extends FormRequest
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
            'index' => $this->getIndexRules(),
            default => []
        };
    }

    private function getIndexRules(): array
    {
        return [
            'date'    => 'required|array|size:2',
            'date.*'  => 'required|date|date_format:Y-m-d',
            'sort'    => 'nullable|string|max:255',
            'order'   => 'nullable|string|in:asc,desc',
            'rows'    => 'nullable|integer|min:1|max:1000',
            'filters' => [
                'nullable',
                'array',
                new SceneRule('CashierDetailIndex')
            ],
        ];
    }

    /**
     * 自定义出错信息
     */
    public function messages(): array
    {
        return match (request()->route()->getActionMethod()) {
            'index' => $this->getIndexMessages(),
            default => []
        };
    }

    private function getIndexMessages(): array
    {
        return [
            'date.required'      => '[查询日期]不能为空',
            'date.array'         => '[查询日期]格式不正确',
            'date.size'          => '[查询日期]必须包含开始和结束日期',
            'date.*.required'    => '[查询日期]格式不正确',
            'date.*.date'        => '[查询日期]格式不正确',
            'date.*.date_format' => '[查询日期]格式必须为Y-m-d',
            'sort.string'        => '[排序字段]格式不正确',
            'sort.max'           => '[排序字段]不能超过255个字符',
            'order.string'       => '[排序方式]格式不正确',
            'order.in'           => '[排序方式]只能是asc或desc',
            'rows.integer'       => '[每页数量]必须为整数',
            'rows.min'           => '[每页数量]至少为1',
            'rows.max'           => '[每页数量]不能超过1000',
        ];
    }
}
