<?php

namespace App\Http\Requests\Web;

use App\Rules\Web\SceneRule;
use Illuminate\Foundation\Http\FormRequest;

class ReportCashierRequest extends FormRequest
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
            'collect' => $this->getCollectRules(),
            'department' => $this->getDepartmentRules(),
            'depositReceived' => $this->getDepositReceivedRules(),
            'list' => $this->getListRules(),
            'arrearage' => $this->getArrearageRules(),
        };
    }

    public function messages(): array
    {
        return match (request()->route()->getActionMethod()) {
            default => [],
            'collect' => $this->getCollectMessages(),
            'department' => $this->getDepartmentMessages(),
            'depositReceived' => $this->getDepositReceivedMessages(),
            'list' => $this->getListMessages(),
            'arrearage' => $this->getArrearageMessages(),
        };
    }

    private function getCollectRules(): array
    {
        return [
            'created_at'   => 'required|array|size:2',
            'created_at.*' => 'date_format:Y-m-d',
        ];
    }

    private function getCollectMessages(): array
    {
        return [
            'created_at.required'      => '[收款日期范围]不能为空!',
            'created_at.array'         => '[收款日期范围]格式错误',
            'created_at.size'          => '[收款日期范围]必须包含开始和结束日期',
            'created_at.*.date_format' => '[收款日期范围] Y-m-d',
        ];
    }

    private function getDepartmentRules(): array
    {
        return [
            'created_at'   => 'required|array|size:2',
            'created_at.*' => 'date_format:Y-m-d',
            'user_id'      => 'nullable|integer|exists:users,id'
        ];
    }

    private function getDepartmentMessages(): array
    {
        return [
            'created_at.required'      => '[收款日期范围]不能为空!',
            'created_at.array'         => '[收款日期范围]格式错误',
            'created_at.size'          => '[收款日期范围]必须包含开始和结束日期',
            'created_at.*.date_format' => '[收款日期范围] Y-m-d',
            'user_id.integer'          => '[收费员]格式错误',
            'user_id.exists'           => '[收费员]不存在',
        ];
    }

    private function getDepositReceivedRules(): array
    {
        return [
            'page'    => 'nullable|integer|min:1',
            'rows'    => 'nullable|integer|min:1|max:100',
            'date'    => 'required|array|size:2',
            'date.*'  => 'date_format:Y-m-d',
            'keyword' => 'nullable|string|max:255',
        ];
    }

    private function getDepositReceivedMessages(): array
    {
        return [
            'page.integer'       => '[页码]必须是整数',
            'page.min'           => '[页码]必须大于0',
            'rows.integer'       => '[每页条数]必须是整数',
            'rows.min'           => '[每页条数]必须大于0',
            'rows.max'           => '[每页条数]不能超过100',
            'date.required'      => '[日期范围]不能为空',
            'date.array'         => '[日期范围]格式错误',
            'date.size'          => '[日期范围]必须包含开始和结束日期',
            'date.*.date_format' => '[日期范围]格式必须为 Y-m-d',
            'keyword.string'     => '[关键词]必须是字符串',
            'keyword.max'        => '[关键词]最多255个字符',
        ];
    }

    /**
     * 收费明细表验证规则
     */
    private function getListRules(): array
    {
        return [
            'filters'      => [
                'nullable',
                'array',
                new SceneRule('ReportCashierList'),
            ],
            'page'         => 'nullable|integer|min:1',
            'rows'         => 'nullable|integer|min:1|max:100',
            'sort'         => 'nullable|string',
            'order'        => 'nullable|string|in:asc,desc',
            'created_at'   => 'required|array|size:2',
            'created_at.0' => 'required|date',
            'created_at.1' => 'required|date|after_or_equal:created_at.0',
            'keyword'      => 'nullable|string|max:100',
        ];
    }

    /**
     * 收费明细表错误消息
     */
    private function getListMessages(): array
    {
        return [
            'filters.array'               => '筛选条件必须为数组',
            'page.integer'                => '[页码]必须是整数',
            'page.min'                    => '[页码]必须大于0',
            'rows.integer'                => '[每页条数]必须是整数',
            'rows.min'                    => '[每页条数]必须大于0',
            'rows.max'                    => '[每页条数]不能超过100',
            'sort.string'                 => '[排序字段]必须是字符串',
            'order.string'                => '[排序方式]必须是字符串',
            'order.in'                    => '[排序方式]只能是 asc 或 desc',
            'created_at.required'         => '[日期范围]不能为空',
            'created_at.array'            => '[日期范围]必须为数组',
            'created_at.size'             => '[日期范围]必须包含开始和结束日期',
            'created_at.0.required'       => '[开始日期]不能为空',
            'created_at.0.date'           => '[开始日期]格式不正确',
            'created_at.1.required'       => '[结束日期]不能为空',
            'created_at.1.date'           => '[结束日期]格式不正确',
            'created_at.1.after_or_equal' => '[结束日期]不能早于开始日期',
            'keyword.string'              => '[关键词]必须是字符串',
            'keyword.max'                 => '[关键词]最多100个字符',
        ];
    }

    /**
     * 应收账款表验证规则
     */
    private function getArrearageRules(): array
    {
        return [
            'page'    => 'nullable|integer|min:1',
            'rows'    => 'nullable|integer|min:1|max:100',
            'date'    => 'required|array|size:2',
            'date.*'  => 'date_format:Y-m-d',
            'keyword' => 'nullable|string|max:255',
        ];
    }

    /**
     * 应收账款表错误消息
     */
    private function getArrearageMessages(): array
    {
        return [
            'page.integer'       => '[页码]必须是整数',
            'page.min'           => '[页码]必须大于0',
            'rows.integer'       => '[每页条数]必须是整数',
            'rows.min'           => '[每页条数]必须大于0',
            'rows.max'           => '[每页条数]不能超过100',
            'date.required'      => '[日期范围]不能为空',
            'date.array'         => '[日期范围]格式错误',
            'date.size'          => '[日期范围]必须包含开始和结束日期',
            'date.*.date_format' => '[日期范围]格式必须为 Y-m-d',
            'keyword.string'     => '[关键词]必须是字符串',
            'keyword.max'        => '[关键词]最多255个字符',
        ];
    }
}
