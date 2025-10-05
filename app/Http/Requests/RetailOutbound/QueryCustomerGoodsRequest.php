<?php

namespace App\Http\Requests\RetailOutbound;

use Illuminate\Foundation\Http\FormRequest;

class QueryCustomerGoodsRequest extends FormRequest
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
            'customer_id'  => 'required|exists:customer,id',
            'warehouse_id' => 'required|exists:warehouse,id'
        ];
    }

    public function messages(): array
    {
        return [
            'customer_id.required'  => 'customer_id参数不能为空!',
            'customer_id.exists'    => '顾客信息不存在!',
            'warehouse_id.required' => 'warehouse_id参数不能为空!',
            'warehouse_id.exists'   => '没有找到仓库信息!'
        ];
    }
}
