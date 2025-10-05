<?php

namespace App\Http\Requests\Consumable;

use App\Models\Goods;
use App\Models\GoodsUnit;
use App\Models\Consumable;
use App\Models\InventoryBatchs;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'form'                         => 'required|array',
            'form.date'                    => 'required|date_format:Y-m-d',
            'form.customer_id'             => 'required|exists:customer,id',
            'form.warehouse_id'            => 'required|exists:warehouse,id',
            'form.department_id'           => 'required|exists:department,id',
            'form.customer_product_id'     => 'required|exists:customer_product,id',
            'form.product_id'              => 'required|exists:product,id',
            'form.product_name'            => 'required',
            'form.user_id'                 => 'required|exists:users,id',
            'detail'                       => 'required|array',
            'detail.*.goods_id'            => 'required|exists:goods,id',
            'detail.*.goods_name'          => 'required',
            'detail.*.specs'               => 'nullable',
            'detail.*.manufacturer_id'     => 'nullable|exists:manufacturer,id',
            'detail.*.manufacturer_name'   => 'nullable',
            'detail.*.inventory_batchs_id' => [
                'required',
                'exists:inventory_batchs,id',
                function ($attribute, $value, $fail) {
                    $number     = $this->input(str_replace('inventory_batchs_id', 'number', $attribute));
                    $unit_id    = $this->input(str_replace('inventory_batchs_id', 'unit_id', $attribute));
                    $goods_id   = $this->input(str_replace('inventory_batchs_id', 'goods_id', $attribute));
                    $goods_name = $this->input(str_replace('inventory_batchs_id', 'goods_name', $attribute));
                    $batch_code = $this->input(str_replace('inventory_batchs_id', 'batch_code', $attribute));

                    // 同一个商品的同一个批次,不能出现两次
                    if (collect($this->input('detail'))->where('goods_id', $goods_id)->where('batch_code', $batch_code)->count() > 1) {
                        $fail("[{$goods_name}]批次号[{$batch_code}]不能重复!");
                    }

                    // 判断出库商品数量
                    $currentUnit    = GoodsUnit::query()->where('goods_id', $goods_id)->where('unit_id', $unit_id)->first();
                    $inventoryBatch = InventoryBatchs::query()->find($value);
                    $amount         = bcmul($number, $currentUnit->rate, 4);

                    if ($amount > $inventoryBatch->number) {
                        $fail("[{$goods_name}]批次[{$batch_code}]库存数量不足!");
                    }
                }
            ]
        ];
    }

    public function messages(): array
    {
        return [
            'form.customer_product_id.exists' => '消费项目不存在!'
        ];
    }

    /**
     * 主表数据
     * @return array
     */
    public function formData(): array
    {
        return [
            'key'                 => 'YLDJ' . date('Ymd') . str_pad((Consumable::query()->today()->count() + 1), 4, '0', STR_PAD_LEFT),
            'date'                => $this->input('form.date'),
            'customer_id'         => $this->input('form.customer_id'),
            'warehouse_id'        => $this->input('form.warehouse_id'),
            'department_id'       => $this->input('form.department_id'),
            'amount'              => collect($this->input('detail'))->sum('amount'),
            'customer_product_id' => $this->input('form.customer_product_id') ?? '',
            'product_name'        => $this->input('form.product_name') ?? '',
            'product_id'          => $this->input('form.product_id') ?? '',
            'user_id'             => $this->input('form.user_id'),
            'create_user_id'      => user()->id,
            'remark'              => $this->input('form.remark')
        ];
    }

    /**
     * 用料明细数据
     * @param $consumable
     * @return array
     */
    public function detailData($consumable): array
    {
        $data    = [];
        $details = $this->input('detail');

        foreach ($details as $k => $v) {
            $data[] = [
                'key'                 => $consumable->key,
                'date'                => $consumable->date,
                'customer_id'         => $consumable->customer_id,
                'warehouse_id'        => $consumable->warehouse_id,
                'department_id'       => $consumable->department_id,
                'goods_id'            => $v['goods_id'],
                'goods_name'          => $v['goods_name'],
                'specs'               => $v['specs'],
                'manufacturer_id'     => $v['manufacturer_id'] ?? null,
                'manufacturer_name'   => $v['manufacturer_name'] ?? null,
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
                'remark'              => $v['remark'],
            ];
        }

        return $data;
    }

    /**
     * 转换出库明细表单位
     * @param $detail
     * @return array
     */
    public function transformers($detail): array
    {
        return [
            'number' => $detail->inventoryBatch->number - bcmul($detail->number, $detail->currentUnit->rate, 4),
            'amount' => $detail->inventoryBatch->amount - $detail->amount
        ];
    }

    /**
     * 库存批次变动明细
     * @param $consumable
     * @return array
     */
    public function inventoryDetailData($consumable): array
    {
        $data = [];

        foreach ($consumable->details as $detail) {
            $goods          = Goods::query()->find($detail->goods_id);
            $basicUnit      = $detail->basicUnit;
            $currentUnit    = $detail->currentUnit;
            $inventoryBatch = InventoryBatchs::query()->find($detail->inventory_batchs_id);

            $insert = [
                'inventory_batchs_id' => $detail->inventory_batchs_id,
                'key'                 => $consumable->key,
                'date'                => $consumable->date,
                'warehouse_id'        => $consumable->warehouse_id,
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
