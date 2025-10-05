<?php

namespace App\Http\Requests\InventoryTransfer;

use App\Models\Goods;
use App\Models\InventoryBatchs;
use App\Models\InventoryTransferDetail;
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
            'id' => [
                'required',
                'exists:inventory_transfer,id,status,1',
                function ($attribute, $id, $fail) {
                    $details = InventoryTransferDetail::query()->where('inventory_transfer_id', $id)->get();

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
            ]
        ];
    }

    public function messages(): array
    {
        return [
            'id.required' => 'id参数不能为空!',
            'id.exists'   => '单据不存在或者已审核!'
        ];
    }

    /**
     * 库存批次调拨出库
     * @param $detail
     * @return array
     */
    public function inventoryBatchTransferOut($detail): array
    {
        $basicUnit   = $detail->basicUnit;
        $currentUnit = $detail->currentUnit;

        $updateData = [
            'number' => $detail->inventoryBatch->number - $detail->number,
            'amount' => $detail->inventoryBatch->amount - $detail->amount
        ];

        // 调拨商品单位,非基本单位
        if ($basicUnit->unit_id != $currentUnit->unit_id) {
            $updateData['number'] = $detail->inventoryBatch->number - bcmul($detail->number, $currentUnit->rate, 4);
        }

        return $updateData;
    }

    /**
     * 库存批次调拨入库
     * @param $detail
     * @return array
     */
    public function inventoryBatchTransferIn($detail): array
    {
        $basicUnit   = $detail->basicUnit;
        $currentUnit = $detail->currentUnit;

        $insert = [
            'goods_id'          => $detail->goods_id,
            'goods_name'        => $detail->goods_name,
            'specs'             => $detail->specs,
            'warehouse_id'      => $detail->in_warehouse_id,
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
            'remark'            => $detail->remark,
        ];

        // 调拨商品单位,非基本单位
        if ($basicUnit->unit_id != $currentUnit->unit_id) {
            $insert['number']    = bcmul($detail->number, $currentUnit->rate, 4);    // 高精度，乘法
            $insert['unit_id']   = $basicUnit->unit_id;
            $insert['unit_name'] = get_unit_name($basicUnit->unit_id);
            $insert['price']     = bcdiv($detail->amount, $insert['number'], 4);     // 总价/数量
        }

        return $insert;
    }

    /**
     * 更新库存批次信息
     * @param $detail
     * @param $inventoryBatch
     * @return array
     */
    public function updateInventoryBatch($detail, $inventoryBatch): array
    {
        $basicUnit   = $detail->basicUnit;
        $currentUnit = $detail->currentUnit;

        $updateData = [
            'number' => $inventoryBatch->number + $detail->number,
            'amount' => $inventoryBatch->amount + $detail->amount
        ];

        // 调拨商品单位,非基本单位
        if ($basicUnit->unit_id != $currentUnit->unit_id) {
            $updateData['number'] = $inventoryBatch->number + bcmul($detail->number, $currentUnit->rate, 4);
        }

        return $updateData;
    }

    /**
     * 调拨出库变动明细
     * @param $transfer
     * @param $detail
     * @return array
     */
    public function inventoryDetailTransferOut($transfer, $detail): array
    {
        $goods          = Goods::query()->find($detail->goods_id);
        $basicUnit      = $detail->basicUnit;
        $currentUnit    = $detail->currentUnit;
        $inventoryBatch = InventoryBatchs::query()->find($detail->inventory_batchs_id);

        $insert = [
            'inventory_batchs_id' => $detail->inventory_batchs_id,
            'key'                 => $transfer->key,
            'date'                => $transfer->date,
            'warehouse_id'        => $transfer->out_warehouse_id,
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

        return $insert;
    }

    /**
     * 调拨入库变动明细
     * @param $transfer
     * @param $detail
     * @param $inventory_batchs_id
     * @return array
     */
    public function inventoryDetailTransferIn($transfer, $detail, $inventory_batchs_id): array
    {
        $goods          = Goods::query()->find($detail->goods_id);
        $basicUnit      = $detail->basicUnit;
        $currentUnit    = $detail->currentUnit;
        $inventoryBatch = InventoryBatchs::query()->find($inventory_batchs_id);

        $insert = [
            'inventory_batchs_id' => $inventory_batchs_id,
            'key'                 => $transfer->key,
            'date'                => $transfer->date,
            'warehouse_id'        => $transfer->in_warehouse_id,
            'goods_id'            => $detail->goods_id,
            'goods_name'          => $detail->goods_name,
            'specs'               => $detail->specs,
            'price'               => $detail->price,   // 单价
            'number'              => $detail->number,  // 数量
            'amount'              => $detail->amount,  // 总价
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
            $insert['number']    = bcmul($detail->number, $currentUnit->rate, 4);    // 高精度，乘法
            $insert['unit_id']   = $basicUnit->unit_id;
            $insert['unit_name'] = get_unit_name($basicUnit->unit_id);
            $insert['price']     = bcdiv($detail->amount, $insert['number'], 4);     // 总价/数量
        }

        return $insert;
    }
}
