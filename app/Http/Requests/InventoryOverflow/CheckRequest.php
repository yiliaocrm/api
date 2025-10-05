<?php

namespace App\Http\Requests\InventoryOverflow;

use App\Models\Goods;
use App\Models\GoodsUnit;
use App\Models\InventoryBatchs;
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
    public function rules()
    {
        return [
            'id' => 'required|exists:inventory_overflows,id,status,1',
        ];
    }

    public function messages(): array
    {
        return [
            'id.exists' => '单据不存在,或者单据状态错误!'
        ];
    }

    /**
     * 创建批次信息
     * @param $overflow
     * @param $detail
     * @return array
     */
    public function inventoryBatchsData($overflow, $detail): array
    {
        $basicUnit   = $detail->basicUnit;
        $currentUnit = $detail->currentUnit;

        $insert = [
            'goods_id'          => $detail->goods_id,
            'goods_name'        => $detail->goods_name,
            'specs'             => $detail->specs,
            'warehouse_id'      => $overflow->warehouse_id,
            'price'             => $detail->price,
            'number'            => $detail->number,
            'unit_id'           => $detail->unit_id,
            'unit_name'         => $detail->unit_name,
            'amount'            => $detail->amount,
            'manufacturer_id'   => $detail->manufacturer_id,
            'manufacturer_name' => $detail->manufacturer_name,
            'production_date'   => $detail->production_date,
            'expiry_date'       => $detail->expiry_date,
            'batch_code'        => $detail->batch_code,
            'sncode'            => $detail->sncode,
            'remark'            => $detail->remark
        ];

        // 商品单位,非基本单位
        if ($basicUnit->unit_id != $currentUnit->unit_id) {
            $insert['unit_id']   = $basicUnit->unit_id;
            $insert['unit_name'] = get_unit_name($basicUnit->unit_id);
            $insert['number']    = bcmul($detail->number, $currentUnit->rate, 4);
            $insert['price']     = bcdiv($detail->amount, $insert['number'], 4);
        }

        return $insert;
    }


    /**
     * 更新库存变动明细
     * @param $overflow
     * @return array
     */
    public function inventoryDetailData($overflow): array
    {
        $data = [];

        foreach ($overflow->inventoryBatch as $batchs) {
            $goods_id    = $batchs->goods_id;
            $unit_id     = $batchs->unit_id;
            $baseUnit    = GoodsUnit::query()->where('basic', 1)->where('goods_id', $goods_id)->first();    // 基本单位
            $currentUnit = GoodsUnit::query()->where('unit_id', $unit_id)->where('goods_id', $goods_id)->first();   // 进货单位
            $goods       = Goods::query()->find($goods_id);

            $insert = [
                'inventory_batchs_id' => $batchs->id,
                'key'                 => $overflow->key,
                'date'                => $overflow->date,
                'warehouse_id'        => $overflow->warehouse_id,
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
