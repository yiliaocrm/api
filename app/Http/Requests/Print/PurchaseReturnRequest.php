<?php

namespace App\Http\Requests\Print;

use App\Models\PrintTemplate;
use App\Models\PurchaseReturn;
use Illuminate\Foundation\Http\FormRequest;

class PurchaseReturnRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
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
        return [
            'id' => [
                'required',
                'exists:purchase_return',
                function ($attribute, $value, $fail) {
                    $purchaseReturn = $this->getPurchaseReturn();

                    if ($purchaseReturn->status == 1) {
                        $fail('审核状态无法打印!');
                    }

                    if (!$this->getPrintTemplate()) {
                        $fail('默认打印模板不存在');
                    }
                }
            ]
        ];
    }

    public function messages(): array
    {
        return [
            'id.required' => '采购退货单ID必须填写',
            'id.exists'   => '采购退货单ID不存在'
        ];
    }

    public function getPurchaseReturn()
    {
        return PurchaseReturn::query()->find(
            $this->input('id')
        );
    }

    public function getPrintTemplate()
    {
        return PrintTemplate::query()
            ->where('type', 'purchase_return')
            ->where('default', 1)
            ->first();
    }
}

