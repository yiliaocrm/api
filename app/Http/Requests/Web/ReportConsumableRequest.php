<?php

namespace App\Http\Requests\Web;

use App\Rules\Web\SceneRule;
use Illuminate\Foundation\Http\FormRequest;

class ReportConsumableRequest extends FormRequest
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
            'filters' => [
                'nullable',
                'array',
                new SceneRule('ReportConsumableDetail'),
            ],
            'rows'    => 'required|integer|min:1',
            'page'    => 'required|integer|min:1',
            'keyword' => 'nullable|string'
        ];
    }

    private function getDetailMessages(): array
    {
        return [
            'filters.array'  => '筛选条件必须为数组',
            'rows.required'  => '每页数量不能为空',
            'rows.integer'   => '每页数量必须为整数',
            'rows.min'       => '每页数量不能小于1',
            'page.required'  => '页码不能为空',
            'page.integer'   => '页码必须为整数',
            'page.min'       => '页码不能小于1',
            'keyword.string' => '关键字必须为字符串',
        ];
    }
}
