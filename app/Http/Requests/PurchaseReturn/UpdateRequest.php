<?php

namespace App\Http\Requests\PurchaseReturn;

use App\Models\GoodsUnit;
use App\Models\InventoryBatchs;
use Illuminate\Foundation\Http\FormRequest;

class UpdateRequest extends FormRequest
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
            'id'                => 'required|exists:purchase_return,id,status,1',
            'form'              => 'required|array',
            'form.date'         => 'required|date_format:Y-m-d',
            'form.warehouse_id' => 'required|exists:warehouse,id',
            'form.user_id'      => 'required|exists:users,id',
            'form.supplier_id'  => 'required|exists:supplier,id',
            'detail'            => 'required|array',
            'detail.*.sncode'   => [
                'nullable',
                function ($attribute, $value, $fail) {
                    $number              = $this->input(str_replace('sncode', 'number', $attribute));
                    $goods_id            = $this->input(str_replace('sncode', 'goods_id', $attribute));
                    $goods_name          = $this->input(str_replace('sncode', 'goods_name', $attribute));
                    $unit_id             = $this->input(str_replace('sncode', 'unit_id', $attribute));
                    $batch_code          = $this->input(str_replace('sncode', 'batch_code', $attribute));
                    $inventory_batchs_id = $this->input(str_replace('sncode', 'inventory_batchs_id', $attribute));

                    if ($number !== 1) {
                        $fail("[{$goods_name}]填写了SN码,入库数量必须为1");
                    }

                    // sncode出库必须为基本单位
                    $isBasic = GoodsUnit::query()->where('goods_id', $goods_id)->where('unit_id', $unit_id)->where('basic', 1)->count();
                    if (!$isBasic) {
                        $fail("[{$goods_name}]SN码出库,商品单位必须是最小单位!");
                    }

                    // SN码不存在
                    $exists = InventoryBatchs::query()->where('id', $inventory_batchs_id)->where('sncode', $value)->count();
                    if (!$exists) {
                        $fail("[{$goods_name}]SN码[{$value}]在批次{$batch_code}中无法查询到!");
                    }
                }
            ],
            'detail.*.goods_id' => [
                'bail',
                'required',
                'exists:goods,id',
                function ($attribute, $value, $fail) {
                    $price               = $this->input(str_replace('goods_id', 'price', $attribute));
                    $amount              = $this->input(str_replace('goods_id', 'amount', $attribute));
                    $number              = $this->input(str_replace('goods_id', 'number', $attribute));
                    $unit_id             = $this->input(str_replace('goods_id', 'unit_id', $attribute));
                    $batch_code          = $this->input(str_replace('goods_id', 'batch_code', $attribute));
                    $goods_name          = $this->input(str_replace('goods_id', 'goods_name', $attribute));
                    $inventory_batchs_id = $this->input(str_replace('goods_id', 'inventory_batchs_id', $attribute));

                    // 同一个商品的同一个批次,不能出现两次
                    if (collect($this->input('detail'))->where('goods_id', $value)->where('inventory_batchs_id', $inventory_batchs_id)->count() > 1) {
                        $fail("[{$goods_name}]批次号[{$batch_code}]不能重复!");
                    }

                    // 判断当前的商品单位与价格是否匹配
                    $currentUnit = GoodsUnit::query()->where('goods_id', $value)->where('unit_id', $unit_id)->first();
                    $goodsBatch  = InventoryBatchs::query()->where('id', $inventory_batchs_id)->where('goods_id', $value)->first();

//                    if ($number * $goodsBatch->price * $currentUnit->rate != $amount) {
//                        $fail("商品价格错误!");
//                    }

                    // 判断库存数量
                    if (bcmul($number, $currentUnit->rate) > $goodsBatch->number) {
                        $fail("[{$goods_name}]批次[{$batch_code}]商品库存不足!");
                    }
                }
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'id.required' => 'id参数不能为空!',
            'id.exists'   => '[单据不存在]或者[状态错误]',
        ];
    }

    /**
     * 更新进货
     * @return array
     */
    public function formData(): array
    {
        return [
            'date'          => $this->input('form.date'),
            'user_id'       => $this->input('form.user_id'),
            'warehouse_id'  => $this->input('form.warehouse_id'),
            'supplier_id'   => $this->input('form.supplier_id'),
            'supplier_name' => get_supplier_name($this->input('form.supplier_id')),
            'remark'        => $this->input('form.remark'),
            'amount'        => collect($this->input('detail'))->sum('amount')
        ];
    }

    /**
     * 退货明细
     * @param $purchaseReturn
     * @return array
     */
    public function detailData($purchaseReturn): array
    {
        $data    = [];
        $details = $this->input('detail');

        foreach ($details as $k => $v) {
            $data[] = [
                'key'                 => $purchaseReturn->key,
                'date'                => $purchaseReturn->date,
                'purchase_return_id'  => $purchaseReturn->id,
                'status'              => 1, // 未审核
                'warehouse_id'        => $purchaseReturn->warehouse_id,
                'goods_id'            => $v['goods_id'],
                'goods_name'          => $v['goods_name'],
                'specs'               => $v['specs'],
                'manufacturer_id'     => $v['manufacturer_id'],
                'manufacturer_name'   => $v['manufacturer_name'],
                'inventory_batchs_id' => $v['inventory_batchs_id'],
                'batch_code'          => $v['batch_code'],
                'production_date'     => $v['production_date'],
                'expiry_date'         => $v['expiry_date'],
                'unit_id'             => $v['unit_id'],
                'unit_name'           => $v['unit_name'],
                'price'               => $v['price'],
                'number'              => $v['number'],
                'amount'              => $v['amount'],
                'sncode'              => $v['sncode'],
                'remark'              => $v['remark']
            ];
        }

        return $data;
    }
}
