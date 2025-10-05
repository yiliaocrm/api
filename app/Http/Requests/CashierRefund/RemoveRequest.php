<?php

namespace App\Http\Requests\CashierRefund;

use App\Models\CashierRefund;
use Illuminate\Foundation\Http\FormRequest;

class RemoveRequest extends FormRequest
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
     * @return array
     */
    public function rules(): array
    {
        return [
            'id' => [
                'required',
                function ($attribute, $id, $fail) {
                    $refund = CashierRefund::query()->find($id);
                    if (!$refund) {
                        return $fail('没有找到退款单据!');
                    }
                    if ($refund->status !== 4) {
                        return $fail('不是[退单]状态,无法删除!');
                    }
                }
            ]
        ];
    }
}
