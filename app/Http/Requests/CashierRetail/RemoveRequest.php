<?php

namespace App\Http\Requests\CashierRetail;

use App\Models\CashierRetail;
use Illuminate\Foundation\Http\FormRequest;

class RemoveRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'id' => [
                'required',
                'exists:cashier_retail',
                function ($attribute, $value, $fail) {
                    if (CashierRetail::where('id', $value)->where('status', 2)->count()) {
                        $fail('零售单已收费,无法删除!');
                    }
                }
            ]
        ];
    }

    public function messages()
    {
        return [
            'id.required' => 'ID不能为空!',
            'id.exists'   => '没有找到要删除的单据!',
        ];
    }
}
