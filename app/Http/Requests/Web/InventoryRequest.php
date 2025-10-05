<?php

namespace App\Http\Requests\Web;

use App\Rules\Web\SceneRule;
use Illuminate\Foundation\Http\FormRequest;

class InventoryRequest extends FormRequest
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
            'batch' => $this->getBatchRules(),
            'index' => $this->getIndexRules(),
            default => []
        };
    }

    public function messages(): array
    {
        return match (request()->route()->getActionMethod()) {
            'batch' => $this->getBatchMessages(),
            'index' => $this->getIndexMessages(),
            default => []
        };
    }

    private function getBatchRules(): array
    {
        return [
            'goods_id' => 'required|exists:goods,id'
        ];
    }

    private function getBatchMessages(): array
    {
        return [
            'goods_id.required' => '缺少goods_id参数!',
            'goods_id.exists'   => '没有找到商品信息'
        ];
    }

    private function getIndexRules(): array
    {
        return [
            'type_id' => 'required|exists:goods_type,id',
            'filters' => [
                'nullable',
                'array',
                new SceneRule('InventoryIndex')
            ]
        ];
    }

    private function getIndexMessages(): array
    {
        return [
            'type_id.required' => '缺少[商品分类]参数!',
            'type_id.exists'   => '没有找到[商品分类]信息!'
        ];
    }
}
