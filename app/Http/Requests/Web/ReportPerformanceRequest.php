<?php

namespace App\Http\Requests\Web;

use App\Rules\Web\SceneRule;
use Illuminate\Foundation\Http\FormRequest;

class ReportPerformanceRequest extends FormRequest
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
            'index' => $this->getIndexRules(),
        };
    }

    public function messages(): array
    {
        return match (request()->route()->getActionMethod()) {
            default => [],
            'index' => $this->getIndexMessages(),
        };
    }

    private function getIndexRules(): array
    {
        return [
            'filters'    => [
                'nullable',
                'array',
                new SceneRule('ReportPerformanceSales'),
            ],
            'created_at' => [
                'required',
                'array'
            ],
            'rows'       => 'required|integer|min:1',
            'page'       => 'required|integer|min:1',
            'keyword'    => 'nullable|string'
        ];
    }

    private function getIndexMessages(): array
    {
        return [
            'filters.array'       => '筛选条件必须是一个数组!',
            'created_at.required' => '[工作日期]参数不能为空!',
            'created_at.array'    => '[工作日期]参数格式错误!',
            'rows.required'       => '[每页条数]参数不能为空!',
            'rows.integer'        => '[每页条数]参数必须是一个整数!',
            'rows.min'            => '[每页条数]参数必须大于0!',
            'page.required'       => '[页码]参数不能为空!',
            'page.integer'        => '[页码]参数必须是一个整数!',
            'page.min'            => '[页码]参数必须大于0!',
            'keyword.string'      => '[关键字]参数格式错误!',
        ];
    }
}
