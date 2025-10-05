<?php

namespace App\Http\Requests\PurchaseReturn;

use App\Models\InventoryBatchs;
use App\Models\PurchaseReturnDetail;

use Illuminate\Foundation\Http\FormRequest;

class CheckRequest extends FormRequest
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
                'exists:purchase_return,id,status,1',
                function ($attribute, $id, $fail) {
                    $details = PurchaseReturnDetail::query()->where('purchase_return_id', $id)->get();

                    foreach ($details as $detail) {
                        $currentUnit  = $detail->currentUnit;
                        $currentBatch = $detail->inventoryBatch;

                        // 判断批次、库存
                        if (bcmul($detail->number, $currentUnit->rate) > $currentBatch->number) {
                            $fail("[{$detail->goods_name}]批次:[{$detail->batch_code}]库存不足!");
                            break;
                        }
                    }
                }
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'id . exists' => '单据不存在,或者单据状态错误!'
        ];
    }

    /**
     * 转换退货明细表单位
     * @param $detail
     * @return array
     */
    public function transformers($detail): array
    {
        $basicUnit   = $detail->basicUnit;
        $currentUnit = $detail->currentUnit;

        $updateData = [
            'number' => $detail->inventoryBatch->number - $detail->number,
            'amount' => $detail->inventoryBatch->amount - $detail->amount
        ];

        // 退货商品单位,非基本单位
        if ($basicUnit->unit_id != $currentUnit->unit_id) {
            $updateData['number'] = $detail->inventoryBatch->number - bcmul($detail->number, $currentUnit->rate, 4);
        }

        return $updateData;
    }

    /**
     * 更新库存变动明细
     * @param $purchaseReturn
     * @return array
     */
    public function inventoryDetailData($purchaseReturn): array
    {
        $data = [];

        foreach ($purchaseReturn->details as $detail) {
            $goods          = $detail->goods;
            $basicUnit      = $detail->basicUnit;
            $currentUnit    = $detail->currentUnit;
            $inventoryBatch = InventoryBatchs::query()->find($detail->inventory_batchs_id);

            $insert = [
                'inventory_batchs_id' => $detail->inventory_batchs_id,
                'key'                 => $purchaseReturn->key,
                'date'                => $purchaseReturn->date,
                'warehouse_id'        => $purchaseReturn->warehouse_id,
                'goods_id'            => $detail->goods_id,
                'goods_name'          => $detail->goods_name,
                'specs'               => $detail->specs,
                'price'               => $detail->price,   // 单价
                'number'              => -1 * abs($detail->number),  // 数量
                'amount'              => -1 * abs($detail->amount),  // 总价
                'unit_id'             => $detail->unit_id, // 单位id
                'unit_name'           => $detail->unit_name,
                'manufacturer_id'     => $detail->manufacturer_id,
                'manufacturer_name'   => $detail->manufacturer_name,
                'production_date'     => $detail->production_date,
                'expiry_date'         => $detail->expiry_date,
                'batch_code'          => $detail->batch_code,
                'sncode'              => $detail->sncode,
                'remark'              => $detail->remark,
                'batchs_number'       => $inventoryBatch->number,
                'batchs_amount'       => $inventoryBatch->amount,
                'inventory_number'    => $goods->inventory_number,
                'inventory_amount'    => $goods->inventory_amount
            ];

            // 进货单位非基本单位
            if ($basicUnit->unit_id != $currentUnit->unit_id) {
                $insert['number']    = -1 * abs(bcmul($detail->number, $currentUnit->rate, 4));    // 高精度，乘法
                $insert['unit_id']   = $basicUnit->unit_id;
                $insert['unit_name'] = get_unit_name($basicUnit->unit_id);
                $insert['price']     = bcdiv($detail->amount, $insert['number'], 4);     // 总价/数量
            }

            $data[] = $insert;
        }

        return $data;
    }
}
