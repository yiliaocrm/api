<?php

namespace App\Http\Requests\DepartmentPicking;

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
            'id'                           => 'required|exists:department_picking,id,status,1',
            'form'                         => 'required|array',
            'form.date'                    => 'required|date_format:Y-m-d|before_or_equal:today',
            'form.type_id'                 => 'required|exists:department_picking_types,id',
            'form.warehouse_id'            => 'required|exists:warehouse,id',
            'form.department_id'           => 'required|exists:department,id',
            'form.user_id'                 => 'required|exists:users,id',
            'detail'                       => 'required|array',
            'detail.*.goods_id'            => 'required|exists:goods,id',
            'detail.*.goods_name'          => 'required',
            'detail.*.specs'               => 'nullable',
            'detail.*.manufacturer_id'     => 'nullable|exists:manufacturer,id',
            'detail.*.manufacturer_name'   => 'nullable',
            'detail.*.inventory_batchs_id' => [
                'required',
                function ($attribute, $inventory_batchs_id, $fail) {
                    $number     = $this->input(str_replace('inventory_batchs_id', 'number', $attribute));
                    $unit_id    = $this->input(str_replace('inventory_batchs_id', 'unit_id', $attribute));
                    $goods_id   = $this->input(str_replace('inventory_batchs_id', 'goods_id', $attribute));
                    $goods_name = $this->input(str_replace('inventory_batchs_id', 'goods_name', $attribute));
                    $batch_code = $this->input(str_replace('inventory_batchs_id', 'batch_code', $attribute));

                    // 同一个商品,同一个批次,不能出现两次
                    if (collect($this->input('detail'))->where('inventory_batchs_id', $inventory_batchs_id)->where('goods_id', $goods_id)->count() > 1) {
                        $fail("[{$goods_name}]批次[{$batch_code}]不能重复!");
                    }

                    // 判断出库商品数量
                    $currentUnit    = GoodsUnit::query()->where('goods_id', $goods_id)->where('unit_id', $unit_id)->first();
                    $inventoryBatch = InventoryBatchs::query()->find($inventory_batchs_id);
                    $amount         = bcmul($number, $currentUnit->rate, 4);

                    if ($amount > $inventoryBatch->number) {
                        $fail("[{$goods_name}]批次[{$batch_code}]库存数量不足!");
                    }

                }
            ],
            'detail.*.batch_code'          => [
                'required',
            ],
            'detail.*.unit_id'             => 'required|exists:unit,id',
            'detail.*.unit_name'           => 'required',
            'detail.*.price'               => 'required',
            'detail.*.number'              => 'required',
            'detail.*.amount'              => 'required',
        ];
    }

    public function messages(): array
    {
        return [
            'form.date.before_or_equal' => '[领料日期]不能大于今天!',
            'detail.required'           => '[领料明细]不能为空!'
        ];
    }

    public function formData(): array
    {
        return [
            'date'           => $this->input('form.date'),
            'type_id'        => $this->input('form.type_id'),
            'warehouse_id'   => $this->input('form.warehouse_id'),
            'department_id'  => $this->input('form.department_id'),
            'user_id'        => $this->input('form.user_id'),
            'amount'         => collect($this->input('detail'))->sum('amount'),
            'remark'         => $this->input('form.remark') ?? ''
        ];
    }

    /**
     * 领料明细
     * @param $picking
     * @return array
     */
    public function detailData($picking): array
    {
        $data    = [];
        $details = $this->input('detail');

        foreach ($details as $k => $v) {
            $data[] = [
                'key'                   => $picking->key,
                'date'                  => $picking->date,
                'department_picking_id' => $picking->id,
                'warehouse_id'          => $picking->warehouse_id,
                'department_id'         => $picking->department_id,
                'goods_id'              => $v['goods_id'],
                'goods_name'            => $v['goods_name'],
                'specs'                 => $v['specs'] ?? null,
                'manufacturer_id'       => $v['manufacturer_id'],
                'manufacturer_name'     => $v['manufacturer_name'],
                'inventory_batchs_id'   => $v['inventory_batchs_id'],
                'batch_code'            => $v['batch_code'],
                'production_date'       => $v['production_date'],
                'expiry_date'           => $v['expiry_date'],
                'unit_id'               => $v['unit_id'],
                'unit_name'             => $v['unit_name'],
                'price'                 => $v['price'],
                'number'                => $v['number'],
                'amount'                => $v['amount'],
                'sncode'                => $v['sncode'],
                'remark'                => $v['remark'],
                'status'                => 1,
            ];
        }

        return $data;
    }
}
