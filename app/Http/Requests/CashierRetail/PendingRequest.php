<?php

namespace App\Http\Requests\CashierRetail;

use Illuminate\Foundation\Http\FormRequest;

class PendingRequest extends FormRequest
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
            'customer_id' => 'required|exists:customer,id',
            'medium_id'   => 'required',
            'type'        => 'required'
        ];
    }

    public function messages(): array
    {
        return [
            'customer_id.required' => '缺少customer_id参数!',
        ];
    }

    public function fillData(): array
    {
        $payable   = collect($this->input('detail'))->sum('payable');
        return [
            'customer_id' => $this->input('customer_id'),
            'medium_id'   => $this->input('medium_id'),
            'type'        => $this->input('type'),
            'status'      => 1,
            'payable'     => $payable,
            'remark'      => $this->input('remark'),
            'detail'      => $this->input('detail'),
            'user_id'     => user()->id,
        ];
    }
}
