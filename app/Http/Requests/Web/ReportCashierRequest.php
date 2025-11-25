<?php

namespace App\Http\Requests\Web;

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
        };
    }

    public function messages(): array
    {
        return match (request()->route()->getActionMethod()) {
            default => [],
            'collect' => $this->getCollectMessages(),
            'department' => $this->getDepartmentMessages(),
            'depositReceived' => $this->getDepositReceivedMessages(),
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
}
