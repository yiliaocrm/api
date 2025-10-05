<?php

namespace App\Http\Requests\Coupon;

use App\Models\Coupon;
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
                    $coupon = Coupon::query()->find($id);
                    if (!$coupon) {
                        $fail('找不到卡券信息!');
                    }
                    if ($coupon->issue_total) {
                        $fail('已领取,无法删除!');
                    }
                }
            ]
        ];
    }
}
