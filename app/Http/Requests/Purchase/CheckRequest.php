<?php

namespace App\Http\Requests\Purchase;

use App\Models\Goods;
use App\Models\GoodsUnit;

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
            'id' => 'required|exists:purchase,id,status,1',

        ];
    }

    public function messages(): array
    {
        return [
            'id.exists' => '单据不存在,或者单据状态错误!'
        ];
    }

    /**
     * 库存批次明细
     * @param $purchase
     * @return array
     */
    public function inventoryBatchData($purchase): array
    {
        $data = [];

        foreach ($purchase->details as $detail) {
            $goods_id    = $detail->goods_id;
            $unit_id     = $detail->unit_id;
            $baseUnit    = GoodsUnit::query()->where('basic', 1)->where('goods_id', $goods_id)->first();    // 基本单位
            $currentUnit = GoodsUnit::query()->where('unit_id', $unit_id)->where('goods_id', $goods_id)->first();   // 进货单位

            $insert = [
                'warehouse_id'      => $purchase->warehouse_id,
                'goods_id'          => $goods_id,
                'goods_name'        => $detail->goods_name,
                'specs'             => $detail->specs,
                'price'             => $detail->price,   // 单价
                'number'            => $detail->number,  // 数量
                'amount'            => $detail->amount,  // 总价
                'unit_id'           => $detail->unit_id, // 单位id
                'unit_name'         => $detail->unit_name,
                'manufacturer_id'   => $detail->manufacturer_id,
                'manufacturer_name' => $detail->manufacturer_name,
                'production_date'   => $detail->production_date,
                'expiry_date'       => $detail->expiry_date,
                'batch_code'        => $detail->batch_code,
                'sncode'            => $detail->sncode,
                'remark'            => $detail->remark
            ];

            # 进货单位非基本单位
            if ($baseUnit->unit_id != $unit_id) {
                $insert['number']    = bcmul($detail->number, $currentUnit->rate, 4);    // 高精度，乘法
                $insert['unit_id']   = $baseUnit->unit_id;
                $insert['unit_name'] = get_unit_name($baseUnit->unit_id);
                $insert['price']     = bcdiv($detail->amount, $insert['number'], 4);     // 总价/数量
            }

            $data[] = $insert;
        }

        return $data;
    }

    /**
     * 写入库存明细
     * @param $purchase
     * @return array
     */
    public function inventoryDetailData($purchase): array
    {
        $data = [];

        foreach ($purchase->inventoryBatch as $batchs) {
            $goods_id    = $batchs->goods_id;
            $unit_id     = $batchs->unit_id;
            $baseUnit    = GoodsUnit::query()->where('basic', 1)->where('goods_id', $goods_id)->first();    // 基本单位
            $currentUnit = GoodsUnit::query()->where('unit_id', $unit_id)->where('goods_id', $goods_id)->first();   // 进货单位
            $goods       = Goods::query()->find($goods_id);

            $insert = [
                'inventory_batchs_id' => $batchs->id,
                'key'                 => $purchase->key,
                'date'                => $purchase->date,
                'warehouse_id'        => $purchase->warehouse_id,
                'goods_id'            => $goods_id,
                'goods_name'          => $batchs->goods_name,
                'specs'               => $batchs->specs,
                'price'               => $batchs->price,   // 单价
                'number'              => $batchs->number,  // 数量
                'amount'              => $batchs->amount,  // 总价
                'unit_id'             => $batchs->unit_id, // 单位id
                'unit_name'           => $batchs->unit_name,
                'manufacturer_id'     => $batchs->manufacturer_id,
                'manufacturer_name'   => $batchs->manufacturer_name,
                'production_date'     => $batchs->production_date,
                'expiry_date'         => $batchs->expiry_date,
                'batch_code'          => $batchs->batch_code,
                'sncode'              => $batchs->sncode,
                'remark'              => $batchs->remark,
                'batchs_number'       => $batchs->number,   // 批次库存(结存)数量
                'batchs_amount'       => $batchs->amount,   // 批次库存(结存)金额
                'inventory_number'    => $goods->inventory_number,
                'inventory_amount'    => $goods->inventory_amount
            ];

            // 进货单位非基本单位
            if ($baseUnit->unit_id != $unit_id) {
                $insert['number']    = bcmul($batchs->number, $currentUnit->rate, 4);    // 高精度，乘法
                $insert['unit_id']   = $baseUnit->unit_id;
                $insert['unit_name'] = get_unit_name($baseUnit->unit_id);
                $insert['price']     = bcdiv($batchs->amount, $insert['number'], 4);     // 总价/数量
            }

            $data[] = $insert;
        }

        return $data;
    }
}
