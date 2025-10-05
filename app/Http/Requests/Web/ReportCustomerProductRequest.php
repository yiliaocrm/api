<?php

namespace App\Http\Requests\Web;

use Illuminate\Foundation\Http\FormRequest;

class ReportCustomerProductRequest extends FormRequest
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
            'ranking' => $this->getRankingRules(),
        };
    }

    public function messages(): array
    {
        return match (request()->route()->getActionMethod()) {
            default => [],
            'ranking' => $this->getRankingMessages(),
        };
    }

    private function getRankingRules(): array
    {
        return [
            'created_at'   => 'required|array|size:2',
            'created_at.*' => 'required|date',
            'medium_id'    => 'nullable|integer|exists:medium,id',
            'type_id'      => 'nullable|integer|exists:product_type,id',
        ];
    }

    private function getRankingMessages(): array
    {
        return [
            'created_at.required'   => '[消费日期]不能为空',
            'created_at.array'      => '[消费日期]格式错误',
            'created_at.size'       => '[消费日期]必须包含开始和结束日期',
            'created_at.*.required' => '[消费日期]不能为空',
            'created_at.*.date'     => '[消费日期]格式错误',
            'medium_id.integer'     => '[媒介来源]格式错误',
            'medium_id.exists'      => '[媒介来源]不存在',
            'type_id.integer'       => '[项目分类]格式错误',
            'type_id.exists'        => '[项目分类]不存在',
        ];
    }
}
