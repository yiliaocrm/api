<?php

namespace App\Http\Requests\CashierArrearage;

use Illuminate\Foundation\Http\FormRequest;

class FreeRequest extends FormRequest
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
            'id' => 'required|exists:cashier_arrearage'
        ];
    }

    public function messages()
    {
        return [
            'id.required' => '缺少id参数',
        ];
    }

    /**
     * 免单还款记录
     * @param $arrearage
     * @return array
     */
    public function detailsData($arrearage)
    {
        return [
            'cashier_arrearage_id' => $arrearage->id,
            'customer_id'          => $arrearage->customer_id,
            'cashier_id'           => $arrearage->cashier_id,
            'package_id'           => $arrearage->package_id,
            'package_name'         => $arrearage->package_name,
            'product_id'           => $arrearage->product_id,
            'product_name'         => $arrearage->product_name,
            'goods_id'             => $arrearage->goods_id,
            'goods_name'           => $arrearage->goods_name,
            'times'                => $arrearage->times,
            'unit_id'              => $arrearage->unit_id,
            'specs'                => $arrearage->specs,
            'income'               => 0,
            'remark'               => '免单处理',
            'salesman'             => $arrearage->salesman,
            'department_id'        => $arrearage->department_id,
            'user_id'              => user()->id
        ];
    }
}
