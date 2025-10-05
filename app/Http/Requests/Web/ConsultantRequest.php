<?php

namespace App\Http\Requests\Web;

use App\Models\ReceptionOrder;
use Illuminate\Foundation\Http\FormRequest;

class ConsultantRequest extends FormRequest
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
            'info' => $this->getInfoRules(),
            'cancel' => $this->getCancelRules(),
        };
    }

    public function messages(): array
    {
        return match (request()->route()->getActionMethod()) {
            default => [],
            'info' => $this->getInfoMessages(),
            'cancel' => $this->getCancelMessages(),
        };
    }

    private function getInfoRules(): array
    {
        return [
            'id' => 'required|exists:reception'
        ];
    }

    private function getInfoMessages(): array
    {
        return [
            'id.required' => '缺少id参数!',
            'id.exists'   => '数据不存在!',
        ];
    }

    private function getCancelRules(): array
    {
        return [
            'id' => [
                'required',
                'exists:reception',
                function ($attribute, $value, $fail) {
                    if (ReceptionOrder::query()->where('reception_id', $value)->first()) {
                        $fail('已经开单,无法取消!');
                    }
                }
            ]
        ];
    }

    private function getCancelMessages(): array
    {
        return [
            'id.required' => 'ID参数不能为空!',
            'id.exists'   => '咨询单不存在',
        ];
    }
}
