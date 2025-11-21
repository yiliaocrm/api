<?php

namespace App\Http\Requests\Web;

use App\Rules\Web\SceneRule;
use Illuminate\Foundation\Http\FormRequest;

class CashierPayRequest extends FormRequest
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
            'update' => $this->getUpdateRules(),
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
                new SceneRule('CashierPayIndex')
            ],
        ];
    }

    private function getUpdateRules(): array
    {
        return [
            'id'          => 'required|exists:cashier_pay',
            'accounts_id' => 'nullable|numeric|not_in:1|exists:accounts,id',
            'remark'      => 'nullable'
        ];
    }

    /**
     * 自定义出错信息
     * @return array|string[]
     */
    public function messages(): array
    {
        return match (request()->route()->getActionMethod()) {
            'index' => $this->getIndexMessages(),
            'update' => $this->getUpdateMessages(),
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

    private function getUpdateMessages(): array
    {
        return [
            'accounts_id.not_in' => '[支付方式]不能改为余额支付!',
        ];
    }

    /**
     * 表单数据
     * @return array
     */
    public function formData(): array
    {
        return match (request()->route()->getActionMethod()) {
            'update' => $this->getUpdateFormData(),
            default => []
        };
    }

    private function getUpdateFormData(): array
    {
        $data = [];

        if ($this->input('accounts_id')) {
            $data['accounts_id'] = $this->input('accounts_id');
        }

        if ($this->has('remark')) {
            $data['remark'] = $this->input('remark');
        }

        return $data;
    }
}
