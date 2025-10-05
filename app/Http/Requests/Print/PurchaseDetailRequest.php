<?php

namespace App\Http\Requests\Print;

use App\Models\Purchase;
use App\Models\PrintTemplate;
use Illuminate\Foundation\Http\FormRequest;

class PurchaseDetailRequest extends FormRequest
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
                'exists:purchase,id',
                function ($attribute, $value, $fail) {
                    $purchase = $this->getPurchase();

                    if ($purchase->status == 1) {
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
            'id.required' => '采购单ID不能为空',
            'id.exists'   => '采购单不存在',
        ];
    }

    public function getPurchase()
    {
        return Purchase::query()->find(
            $this->input('id')
        );
    }

    public function getPrintTemplate()
    {
        return PrintTemplate::query()
            ->where('type', 'purchase_detail')
            ->where('default', 1)
            ->first();
    }
}
