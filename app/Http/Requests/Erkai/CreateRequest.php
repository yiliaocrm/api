<?php

namespace App\Http\Requests\Erkai;

use App\Models\Goods;
use App\Models\GoodsUnit;
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
            'customer_id'         => 'required|exists:customer,id',
            'form'                => 'required|array',
            'form.department_id'  => 'required|exists:department,id',
            'form.type'           => 'required|numeric',
            'form.medium_id'      => 'required|exists:medium,id',
            'detail'              => 'required|array',
            'detail.*.type'       => 'required|in:goods,product',
            'detail.*.package_id' => 'nullable|exists:product_package,id',
            'detail.*.product_id' => [
                'nullable',
                'exists:product,id',
                'required_without:detail.*.goods_id',
            ],
            'detail.*.goods_id'   => [
                'nullable',
                'exists:goods,id',
                'required_without:detail.*.product_id',
                function ($attribute, $value, $fail) {
                    $times      = $this->input(str_replace('goods_id', 'times', $attribute));
                    $unit_id    = $this->input(str_replace('goods_id', 'unit_id', $attribute));
                    $goods_name = $this->input(str_replace('goods_id', 'goods_name', $attribute));

                    // 同一个商品,不能出现两次
                    if (collect($this->input('detail'))->where('goods_id', $value)->count() > 1) {
                        $fail("[{$goods_name}]不能重复!");
                    }

                    // 判断商品数量
                    $goods       = Goods::query()->find($value);
                    $currentUnit = GoodsUnit::query()->where('goods_id', $value)->where('unit_id', $unit_id)->first();
                    $amount      = bcmul($times, $currentUnit->rate, 4);

                    if ($amount > $goods->inventory_number) {
                        $fail("[{$goods_name}]库存数量不足!");
                    }
                }
            ]
        ];
    }

    public function messages(): array
    {
        return [
            'detail.required' => '开单信息不能为空!'
        ];
    }

    /**
     * 主单
     * @return array
     */
    public function formData(): array
    {
        return [
            'customer_id'   => $this->input('customer_id'),
            'department_id' => $this->input('form.department_id'),
            'type'          => $this->input('form.type'),
            'status'        => 1, // 未成交
            'payable'       => collect($this->input('detail'))->sum('payable'),
            'income'        => 0,
            'deposit'       => 0,
            'arrearage'     => 0,
            'medium_id'     => $this->input('form.medium_id'),
            'remark'        => $this->input('form.remark'),
            'user_id'       => user()->id
        ];
    }

    /**
     * 子单
     * @param $erkai
     * @return array
     */
    public function detailData($erkai): array
    {
        $details = $this->input('detail');
        $data    = [];

        foreach ($details as $detail) {
            $data[] = [
                'customer_id'   => $erkai->customer_id,
                'status'        => 2,  // 待收费
                'type'          => $detail['type'],
                'package_id'    => $detail['package_id'] ?? null,
                'package_name'  => $detail['package_name'] ?? null,
                'splitable'     => $detail['splitable'] ?? null,
                'product_id'    => $detail['product_id'] ?? null,
                'product_name'  => $detail['product_name'] ?? null,
                'goods_id'      => $detail['goods_id'] ?? null,
                'goods_name'    => $detail['goods_name'] ?? null,
                'times'         => $detail['times'],
                'unit_id'       => $detail['unit_id'] ?? null,
                'unit_name'     => isset($detail['unit_id']) ? get_unit_name($detail['unit_id']) : null,
                'specs'         => $detail['specs'] ?? null,
                'price'         => $detail['price'],
                'sales_price'   => $detail['sales_price'],
                'payable'       => $detail['payable'],
                'amount'        => 0,
                'department_id' => $detail['department_id'],
                'salesman'      => $detail['salesman'],
                'remark'        => $detail['remark'] ?? null,
                'user_id'       => user()->id,
            ];
        }

        return $data;
    }

    /**
     * 待收费
     * @param $erkai
     * @return array
     */
    public function cashierData($erkai): array
    {
        return [
            'customer_id' => $erkai->customer_id,
            'detail'      => $erkai->details,
            'payable'     => $erkai->details->sum('payable'),
            'income'      => 0,
            'arrearage'   => 0,
            'coupon'      => 0,
            'status'      => 1,             // 未收费状态
            'user_id'     => user()->id,    // 录单人员
        ];
    }
}
