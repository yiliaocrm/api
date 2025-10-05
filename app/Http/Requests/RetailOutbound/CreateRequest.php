<?php

namespace App\Http\Requests\RetailOutbound;

use App\Models\GoodsUnit;
use App\Models\CustomerGoods;
use App\Models\RetailOutbound;
use App\Models\InventoryBatchs;
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
     * 验证规则
     * @return array
     */
    public function rules(): array
    {
        return [
            'customer_id'                   => 'required|exists:customer,id',
            'form'                          => 'required|array',
            'form.date'                     => 'required|date_format:Y-m-d',
            'form.user_id'                  => 'required|exists:users,id',
            'form.department_id'            => 'required|exists:department,id',
            'form.warehouse_id'             => 'required|exists:warehouse,id',
            'details'                       => [
                'required',
                'array',
                function ($attribute, $customer_goods_id, $fail) {
                    $data    = [];
                    $details = $this->input('details');

                    // 出库单位转化为最小单位
                    foreach ($details as $detail) {
                        // 出库单位
                        $unit   = GoodsUnit::query()->where('goods_id', $detail['goods_id'])->where('unit_id', $detail['unit_id'])->first();
                        $data[] = [
                            'goods_id'            => $detail['goods_id'],
                            'goods_name'          => $detail['goods_name'],
                            'number'              => bcmul($detail['number'], $unit->rate, 4),
                            'batch_code'          => $detail['batch_code'],
                            'inventory_batchs_id' => $detail['inventory_batchs_id']
                        ];
                    }

                    // 按商品批次汇总
                    $batchs = collect($data)->unique('inventory_batchs_id')->toArray();
                    foreach ($batchs as $batch) {
                        // 批次数量合计
                        $amount          = collect($data)->where('inventory_batchs_id', $batch['inventory_batchs_id'])->sum('number');
                        $inventoryBatchs = InventoryBatchs::query()->find($batch['inventory_batchs_id']);
                        // 1.对比批次库存数量
                        if ($amount > $inventoryBatchs->number) {
                            $fail("商品[{$batch['goods_name']}]批次[{$batch['batch_code']}]库存不足!");
                        }
                    }
                }
            ],
            'details.*.customer_goods_id'   => [
                'required',
                function ($attribute, $customer_goods_id, $fail) {
                    $goods_name = $this->input(str_replace('customer_goods_id', 'goods_name', $attribute));

                    // 根据customer_goods_id判断,出库明细 合计数量,不能大于 未使用物品数量
                    $amount = collect($this->input('details'))->where('customer_goods_id', $customer_goods_id)->sum('number');
                    $count  = CustomerGoods::query()->where('id', $customer_goods_id)->where('leftover', '>=', $amount)->count();
                    if (!$count) {
                        $fail("[{$goods_name}]出库数量,不能大于已购剩余数量!");
                    }
                }
            ],
            'details.*.cashier_id'          => 'required|exists:cashier,id',
            'details.*.goods_id'            => 'required|exists:goods,id',
            'details.*.goods_name'          => 'required',
            'details.*.package_id'          => 'nullable',
            'details.*.package_name'        => 'nullable',
            'details.*.inventory_batchs_id' => [
                'required',
                'exists:inventory_batchs,id',
                function ($attribute, $value, $fail) {
                    $number     = $this->input(str_replace('inventory_batchs_id', 'number', $attribute));
                    $unit_id    = $this->input(str_replace('inventory_batchs_id', 'unit_id', $attribute));
                    $goods_id   = $this->input(str_replace('inventory_batchs_id', 'goods_id', $attribute));
                    $goods_name = $this->input(str_replace('inventory_batchs_id', 'goods_name', $attribute));
                    $batch_code = $this->input(str_replace('inventory_batchs_id', 'batch_code', $attribute));

                    // 判断[批次id]与[批号]是否匹配
                    if (!InventoryBatchs::query()->where('batch_code', $batch_code)->where('id', $value)->count()) {
                        $fail("批号[{$batch_code}]与ID[{$value}]不匹配!");
                    }

                    // 判断单个出库商品数量
                    $currentUnit    = GoodsUnit::query()->where('goods_id', $goods_id)->where('unit_id', $unit_id)->first();
                    $inventoryBatch = InventoryBatchs::query()->find($value);
                    $amount         = bcmul($number, $currentUnit->rate, 4);

                    if ($amount > $inventoryBatch->number) {
                        $fail("[{$goods_name}]批号[{$batch_code}]库存数量不足!");
                    }
                }
            ],
            'details.*.batch_code'          => 'required',
            'details.*.manufacturer_id'     => 'nullable|exists:manufacturer,id',
            'details.*.manufacturer_name'   => 'nullable',
            'details.*.number'              => [
                'required',
                'numeric',
                // 转为最小单位与批次库存判断
            ],
            'details.*.unit_id'             => 'required|exists:unit,id',
            'details.*.unit_name'           => 'required',
            'details.*.price'               => 'required|numeric',
            'details.*.amount'              => 'required|numeric',
        ];
    }

    /**
     * 错误提示信息
     * @return array
     */
    public function messages(): array
    {
        return [
            'customer_id.required'  => '缺少customer_id参数',
            'customer_id.exists'    => '顾客信息不存在!',
            'form.user_id.required' => '[出料人员]不能为空!',
            'form.user_id.exists'   => '[出料人员]不存在!!'
        ];
    }

    /**
     * 表单数据
     * @return array
     */
    public function formData(): array
    {
        return [
            'key'            => 'LSCL' . date('Ymd') . str_pad((RetailOutbound::today()->count() + 1), 4, '0', STR_PAD_LEFT),
            'date'           => $this->input('form.date'),
            'customer_id'    => $this->input('customer_id'),
            'amount'         => collect($this->input('details'))->sum('amount'),
            'department_id'  => $this->input('form.department_id'),
            'warehouse_id'   => $this->input('form.warehouse_id'),
            'remark'         => $this->input('form.remark'),
            'user_id'        => $this->input('form.user_id'),
            'create_user_id' => user()->id,
        ];
    }

    /**
     * 出料明细
     * @param $retailOutbound
     * @return array
     */
    public function detailData($retailOutbound): array
    {
        $data    = [];
        $details = $this->input('details');

        foreach ($details as $k => $v) {
            $data[] = [
                'retail_outbound_id'  => $retailOutbound->id,
                'key'                 => $retailOutbound->key,
                'date'                => $retailOutbound->date,
                'warehouse_id'        => $retailOutbound->warehouse_id,
                'department_id'       => $retailOutbound->department_id,
                'customer_id'         => $retailOutbound->customer_id,
                'customer_goods_id'   => $v['customer_goods_id'],
                'cashier_id'          => $v['cashier_id'],
                'goods_id'            => $v['goods_id'],
                'goods_name'          => $v['goods_name'],
                'specs'               => $v['specs'],
                'package_id'          => $v['package_id'],
                'package_name'        => $v['package_name'],
                'inventory_batchs_id' => $v['inventory_batchs_id'],
                'batch_code'          => $v['batch_code'],
                'manufacturer_id'     => $v['manufacturer_id'],
                'manufacturer_name'   => $v['manufacturer_name'],
                'production_date'     => $v['production_date'],
                'expiry_date'         => $v['expiry_date'],
                'sncode'              => $v['sncode'],
                'number'              => $v['number'], // 零售出库数量(需要转换为最小单位 对比)
                'unit_id'             => $v['unit_id'],
                'unit_name'           => $v['unit_name'],
                'price'               => $v['price'],
                'amount'              => $v['amount'],
                'remark'              => $v['remark'],
                'user_id'             => $retailOutbound->user_id,
                'create_user_id'      => $retailOutbound->create_user_id
            ];
        }

        return $data;
    }

    /**
     * 更新库存批次信息
     * @param $detail
     * @return array
     */
    public function inventoryBatchsData($detail): array
    {
        $basicUnit      = $detail->basicUnit;
        $currentUnit    = $detail->currentUnit;
        $inventoryBatch = $detail->inventoryBatch;  // 批次

        // 出料数量(单位转换)
        $number = ($basicUnit->unit_id != $currentUnit->unit_id) ? $inventoryBatch->number - bcmul($detail->number, $currentUnit->rate, 4) : $inventoryBatch->number - $detail->number;

        return [
            'number' => $number,
            'amount' => bcmul($inventoryBatch->price, $number, 4)
        ];
    }

    /**
     * 顾客已购物品记录
     * @param $detail
     * @return array
     */
    public function customerGoodsData($detail): array
    {
        $basicUnit   = $detail->basicUnit;
        $currentUnit = $detail->currentUnit;

        $used     = ($basicUnit->unit_id != $currentUnit->unit_id) ? bcmul($detail->number, $currentUnit->rate, 4) : $detail->number;  // 使用数量(转换为最小单位)
        $leftover = $detail->customerGoods->leftover - $used;   // 剩余数量
        $status   = $leftover == 0 ? 2 : 4;   // 部分出库 或 全部出库

        return [
            'status'   => $status,
            'leftover' => $leftover,
            'used'     => $used
        ];
    }

    /**
     * 库存变动明细
     * @param $retailOutbound
     * @return array
     */
    public function inventoryDetailData($retailOutbound): array
    {
        $data    = [];
        $details = $retailOutbound->details;

        foreach ($details as $detail) {
            $goods          = $detail->goods;
            $basicUnit      = $detail->basicUnit;
            $currentUnit    = $detail->currentUnit;
            $inventoryBatch = InventoryBatchs::query()->find($detail->inventory_batchs_id);

            $insert = [
                'inventory_batchs_id' => $detail->inventory_batchs_id,
                'key'                 => $retailOutbound->key,
                'date'                => $retailOutbound->date,
                'warehouse_id'        => $retailOutbound->warehouse_id,
                'goods_id'            => $detail->goods_id,
                'goods_name'          => $detail->goods_name,
                'specs'               => $detail->specs,
                'price'               => $inventoryBatch->price,   // 批次单价
                'number'              => -1 * abs($detail->number),  // 数量
                'amount'              => -1 * abs(bcmul($inventoryBatch->price, $detail->number, 4)),  // 总价
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

            // 出料单位非基本单位
            if ($basicUnit->unit_id != $currentUnit->unit_id) {
                $insert['number']    = -1 * abs(bcmul($detail->number, $currentUnit->rate, 4));    // 高精度，乘法
                $insert['unit_id']   = $basicUnit->unit_id;
                $insert['unit_name'] = get_unit_name($basicUnit->unit_id);
                $insert['amount']    = bcmul($inventoryBatch->price, $insert['number'], 4);     // 总价/数量
            }

            $data[] = $insert;
        }

        return $data;
    }
}
