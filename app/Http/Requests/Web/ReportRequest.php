<?php

namespace App\Http\Requests\Web;

use App\Rules\Web\SceneRule;
use Illuminate\Foundation\Http\FormRequest;

class ReportRequest extends FormRequest
{
    /**
     * [场景化搜索]方法与页面的映射关系
     * @var array
     */
    private array $pages = [
        'customerRefund' => 'ReportCustomerRefund',
    ];

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
            'customerRefund' => $this->getReportRules(),
            default => []
        };
    }

    public function messages(): array
    {
        return match (request()->route()->getActionMethod()) {
            'customerRefund' => $this->getReportMessages(),
            default => []
        };
    }

    private function getReportRules(): array
    {
        $rules = [
            'filters' => ['nullable', 'array']
        ];

        $method = request()->route()->getActionMethod();

        if (isset($this->pages[$method])) {
            $rules['filters'][] = new SceneRule($this->pages[$method]);
        }

        return $rules;
    }

    private function getReportMessages(): array
    {
        return [
            'filters.array' => '筛选条件必须是数组',
        ];
    }
}
