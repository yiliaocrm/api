<?php

namespace App\Http\Requests\Miniapp;

use App\Rules\Web\SceneRule;
use Illuminate\Foundation\Http\FormRequest;

class UserIndexRequest extends FormRequest
{
    /**
     * 允许已登录机构端用户查询小程序绑定用户列表。
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * 小程序用户列表查询参数校验规则。
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'rows' => ['nullable', 'integer', 'min:1'],
            'page' => ['nullable', 'integer', 'min:1'],
            'sort' => ['nullable', 'string', 'in:id,nickname,phone,created_at'],
            'order' => ['nullable', 'string', 'in:asc,desc'],
            'keyword' => ['nullable', 'string'],
            'created_at' => ['nullable', 'array', 'size:2'],
            'created_at.0' => ['required_with:created_at', 'date'],
            'created_at.1' => ['required_with:created_at', 'date'],
            'filters' => ['nullable', 'array', new SceneRule('MiniappUserIndex')],
            'filters.*' => ['required', 'array'],
            'filters.*.field' => ['required', 'string'],
            'filters.*.operator' => ['required', 'string'],
            'filters.*.value' => ['nullable'],
        ];
    }

    /**
     * 校验错误提示。
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'rows.integer' => '[每页数量]格式不正确',
            'page.integer' => '[页码]格式不正确',
            'sort.in' => '[排序字段]值无效',
            'order.in' => '[排序方向]值无效',
            'created_at.array' => '[注册时间]格式不正确',
            'created_at.size' => '[注册时间]必须包含开始和结束日期',
            'created_at.*.date' => '[注册时间]日期格式不正确',
            'filters.array' => '[场景化筛选条件]格式不正确',
            'filters.*.required' => '[场景化筛选条件]格式不正确',
            'filters.*.array' => '[场景化筛选条件]格式不正确',
            'filters.*.field.required' => '[筛选字段]不能为空',
            'filters.*.operator.required' => '[筛选操作符]不能为空',
        ];
    }
}
