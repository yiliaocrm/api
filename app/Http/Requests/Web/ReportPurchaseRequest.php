<?php

namespace App\Http\Requests\Web;

use App\Rules\Web\SceneRule;
use Illuminate\Foundation\Http\FormRequest;

class ReportPurchaseRequest extends FormRequest
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
            'detail' => $this->getDetailRules(),
            default => []
        };
    }

    public function messages(): array
    {
        return match (request()->route()->getActionMethod()) {
            'detail' => $this->getDetailMessages(),
            default => []
        };
    }

    private function getDetailRules(): array
    {
        return [
            'filters' => [
                'nullable',
                'array',
                new SceneRule('ReportPurchaseDetail')
            ]
        ];
    }

    private function getDetailMessages(): array
    {
        return [
            'filters.array' => '筛选条件必须为数组',
        ];
    }
}
