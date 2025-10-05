<?php

namespace App\Http\Requests\Web;

use App\Rules\Web\SceneRule;
use Illuminate\Foundation\Http\FormRequest;

class PurchaseRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return match (request()->route()->getActionMethod()) {
            'manage' => $this->getManageRules(),
            'remove' => $this->getRemoveRules(),
            default => [],
        };
    }

    public function messages(): array
    {
        return match (request()->route()->getActionMethod()) {
            'manage' => $this->getManageMessages(),
            'remove' => $this->getRemoveMessages(),
            default => [],
        };
    }

    private function getRemoveRules(): array
    {
        return [
            'id' => 'required|exists:purchase,id,status,1',
        ];
    }

    private function getRemoveMessages(): array
    {
        return [
            'id.required' => '缺少id参数',
            'id.exists'   => '状态错误,无法删除!',
        ];
    }

    private function getManageRules(): array
    {
        return [
            'filters' => [
                'nullable',
                'array',
                new SceneRule('PurchaseIndex')
            ]
        ];
    }

    private function getManageMessages(): array
    {
        return [
            'filters.array' => '参数错误',
        ];
    }
}
