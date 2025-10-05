<?php

namespace App\Http\Requests\DataMaintenance;

use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;

class CreateCustomerProductRequest extends FormRequest
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
            'customer_id'            => 'required|exists:customer,id',
            'form'                   => 'required|array',
            'form.medium_id'         => 'required|exists:medium,id',
            'form.type'              => 'required',
            'details'                => 'required|array',
            'details.*.product_id'   => 'required|exists:product,id',
            'details.*.product_name' => 'required',
            'details.*.package_id'   => 'nullable|exists:product_package,id',
            'details.*.times'        => 'required|numeric',
            'details.*.price'        => 'required|numeric',
            'details.*.sales_price'  => 'required|numeric',
            'details.*.payable'      => 'required|numeric',
        ];
    }

    public function formData(): array
    {
        $data    = [];
        $details = $this->input('details', []);

        foreach ($details as $detail) {
            $product = Product::query()->find($detail['product_id']);
            $data[]  = [
                'cashier_id'        => 0,
                'cashier_detail_id' => 0,
                'customer_id'       => $this->input('customer_id'),
                'product_id'        => $detail['product_id'],
                'product_name'      => $detail['product_name'],
                'package_id'        => $detail['package_id'],
                'package_name'      => $detail['package_name'],
                'status'            => 1,
                'expire_time'       => null,
                'times'             => $detail['times'],
                'used'              => 0,
                'leftover'          => $detail['times'],
                'refund_times'      => 0,
                'price'             => $detail['price'],
                'sales_price'       => $detail['sales_price'],
                'payable'           => $detail['payable'],
                'income'            => $detail['payable'],
                'deposit'           => 0,
                'coupon'            => 0,
                'arrearage'         => 0,
                'user_id'           => user()->id,
                'consultant'        => null,
                'ek_user'           => null,
                'doctor'            => null,
                'reception_type'    => $this->input('form.type'),
                'medium_id'         => $this->input('form.medium_id'),
                'department_id'     => $detail['department_id'],
                'deduct_department' => $product->deduct_department,
                'salesman'          => $detail['salesman'],
                'remark'            => $this->input('form.remark')
            ];
        }

        return $data;
    }
}
