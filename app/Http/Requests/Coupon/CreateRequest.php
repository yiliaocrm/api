<?php

namespace App\Http\Requests\Coupon;

use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;

class CreateRequest extends FormRequest
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
            'form'              => 'required|array',
            'form.name'         => 'required',
            'form.coupon_value' => 'required',
            'form.total'        => 'required|integer',
            'form.quota'        => 'required|integer|min:0',
            'form.start'        => 'required|date_format:Y-m-d',
            'form.end'          => 'required|date_format:Y-m-d',
            'form.multiple_use' => 'required|boolean',
            'form.sales_price'  => 'required',
            'form.integrals'    => 'required'
        ];
    }

    public function messages(): array
    {
        return [
            'form.name.required'         => '[卡券名称]不能为空!',
            'form.coupon_value.required' => '[卡券金额]不能为空!',
            'form.start.date_format'     => '[活动时间]格式错误!'
        ];
    }

    public function formData(): array
    {
        return [
            'status'         => 1,
            'name'           => $this->input('form.name'),
            'coupon_value'   => $this->input('form.coupon_value'),
            'least_consume'  => $this->input('form.least_consume'),
            'total'          => $this->input('form.total'),
            'issue_count'    => 0,
            'quota'          => $this->input('form.quota'),
            'start'          => $this->input('form.start'),
            'end'            => Carbon::parse($this->input('form.end'))->endOfDay(),
            'multiple_use'   => $this->input('form.multiple_use'),
            'sales_price'    => $this->input('form.sales_price'),
            'integrals'      => $this->input('form.integrals'),
            'rate'           => bcdiv($this->input('form.sales_price'), $this->input('form.coupon_value'), 4),
            'create_user_id' => user()->id,
        ];
    }
}
