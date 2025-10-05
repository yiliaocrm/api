<?php

namespace App\Http\Requests\Coupon;

use Illuminate\Foundation\Http\FormRequest;

class InfoRequest extends FormRequest
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
            'coupon_id' => '|exists:coupons,id'
        ];
    }

    public function messages(): array
    {
        return [
            'coupon_id.required' => '缺少coupon_id参数!',
            'coupon_id.exists'   => '卡券信息不存在!'
        ];
    }
}
