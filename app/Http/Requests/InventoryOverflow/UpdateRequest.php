<?php

namespace App\Http\Requests\InventoryOverflow;

use App\Models\GoodsUnit;
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
            'id'                       => 'required|exists:inventory_overflows,id,status,1',
            'form'                     => 'required|array',
            'form.date'                => 'required|date_format:Y-m-d',
            'form.warehouse_id'        => 'required|exists:warehouse,id',
            'form.department_id'       => 'required|exists:department,id',
            'form.user_id'             => 'required|exists:users,id',
            'detail'                   => 'required|array',
            'detail.*.goods_id'        => 'required|exists:goods,id',
            'detail.*.number'          => 'required|numeric',
            'detail.*.unit_id'         => 'required|numeric|exists:unit,id',
            'detail.*.batch_code'      => 'required',
            'detail.*.sncode'          => [
                'nullable',
                'unique:inventory_batchs,sncode',
                function ($attribute, $value, $fail) {
                    $number     = $this->input(str_replace('sncode', 'number', $attribute));
                    $unit_id    = $this->input(str_replace('sncode', 'unit_id', $attribute));
                    $goods_id   = $this->input(str_replace('sncode', 'goods_id', $attribute));
                    $goods_name = $this->input(str_replace('sncode', 'goods_name', $attribute));

                    if ($number !== 1) {
                        $fail("[{$goods_name}]填写了SN码,入库数量必须为1");
                    }

                    $isBasic = GoodsUnit::query()->where('goods_id', $goods_id)->where('unit_id', $unit_id)->where('basic', 1)->count();
                    if (!$isBasic) {
                        $fail("[{$goods_name}]SN码入库单位必须是基本单位!");
                    }
                }
            ],
            'detail.*.expiry_date'     => 'nullable|date_format:Y-m-d',
            'detail.*.production_date' => 'nullable|date_format:Y-m-d',
        ];
    }

    public function messages(): array
    {
        return [
            'id.required'                          => '缺少id参数!',
            'id.exists'                            => '没有找到数据!',
            'form.date.required'                   => '单据日期不能为空',
            'form.date.date_format'                => '单据日期格式错误',
            'form.user_id.required'                => '经办人信息不能为空',
            'form.user_id.exists'                  => '系统后台没有找到经办人信息',
            'form.warehouse_id.required'           => '进货仓库信息不能为空',
            'form.warehouse_id.exists'             => '系统后台没有找到进货仓库信息',
            'detail.*.batch_code.required'         => '商品批号不能为空',
            'detail.*.sncode.unique'               => 'SN码不能重复',
            'detail.*.expiry_date.date_format'     => '[过期时间]格式错误!',
            'detail.*.production_date.date_format' => '[生产日期]格式错误!',
        ];
    }

    public function formData(): array
    {
        return [
            'date'           => $this->input('form.date'),
            'warehouse_id'   => $this->input('form.warehouse_id'),
            'user_id'        => $this->input('form.user_id'),
            'department_id'  => $this->input('form.department_id'),
            'amount'         => collect($this->input('detail'))->sum('amount'),
            'remark'         => $this->input('form.remark') ?? '',
            'create_user_id' => user()->id
        ];
    }

    /**
     * 报溢明细
     * @param $overflow
     * @return array
     */
    public function detailData($overflow): array
    {
        $data    = [];
        $details = $this->input('detail');

        foreach ($details as $k => $v) {
            $data[] = [
                'key'                   => $overflow->key,
                'date'                  => $overflow->date,
                'status'                => $overflow->status,
                'warehouse_id'          => $overflow->warehouse_id,
                'goods_id'              => $v['goods_id'],
                'goods_name'            => $v['goods_name'],
                'specs'                 => $v['specs'],
                'price'                 => $v['price'],
                'number'                => $v['number'],
                'unit_id'               => $v['unit_id'],
                'unit_name'             => $v['unit_name'],
                'amount'                => $v['amount'],
                'manufacturer_id'       => $v['manufacturer_id'] ?? null,
                'manufacturer_name'     => $v['manufacturer_name'] ?? null,
                'production_date'       => $v['production_date'] ?? null,
                'expiry_date'           => $v['expiry_date'] ?? null,
                'batch_code'            => $v['batch_code'] ?? null,
                'sncode'                => $v['sncode'] ?? null,
                'remark'                => $v['remark'] ?? null
            ];
        }

        return $data;
    }
}
