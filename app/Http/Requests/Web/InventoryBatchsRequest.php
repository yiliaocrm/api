<?php

namespace App\Http\Requests\Web;

use App\Rules\Web\SceneRule;
use Illuminate\Foundation\Http\FormRequest;

class InventoryBatchsRequest extends FormRequest
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
            'detail' => $this->getDetailRules(),
            default => []
        };
    }

    public function messages(): array
    {
        return match (request()->route()->getActionMethod()) {
            'index' => $this->getIndexMessages(),
            'detail' => $this->getDetailMessages(),
            default => []
        };
    }

    private function getIndexRules(): array
    {
        return [
            'filters' => [
                'nullable',
                'array',
                new SceneRule('InventoryBatchsIndex')
            ]
        ];
    }

    private function getIndexMessages(): array
    {
        return [
            'filters.array' => '筛选条件必须是数组',
        ];
    }

    private function getDetailRules(): array
    {
        return [
            'inventory_batchs_id' => 'required|integer|exists:inventory_batchs,id'
        ];
    }

    private function getDetailMessages(): array
    {
        return [
            'inventory_batchs_id.required' => '库存批次ID不能为空',
            'inventory_batchs_id.integer'  => '库存批次ID必须是整数',
            'inventory_batchs_id.exists'   => '库存批次ID不存在'
        ];
    }
}
