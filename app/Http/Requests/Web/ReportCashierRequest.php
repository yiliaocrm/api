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
        };
    }

    public function messages(): array
    {
        return match (request()->route()->getActionMethod()) {
            default => [],
            'collect' => $this->getCollectMessages(),
            'department' => $this->getDepartmentMessages(),
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
}
