<?php

namespace App\Http\Requests\Purchase;

use App\Models\Purchase;
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
            'form'                     => 'required|array',
            'form.date'                => 'required|date_format:Y-m-d|before_or_equal:today',
            'form.warehouse_id'        => 'required|exists:warehouse,id',
            'form.type_id'             => 'required|exists:purchase_type,id',
            'form.user_id'             => 'required|exists:users,id',
            'form.supplier_id'         => 'required|exists:supplier,id',
            'detail'                   => 'required|array',
            'detail.*.goods_id'        => 'required|exists:goods,id',
            'detail.*.number'          => 'required|numeric',
            'detail.*.unit_id'         => 'required|numeric|exists:unit,id',
            'detail.*.batch_code'      => 'required|unique:purchase_detail',
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
            'form.date.required'                   => '单据日期不能为空',
            'form.date.date_format'                => '单据日期格式错误',
            'form.date.before_or_equal'            => '[单据日期]不能大于今天!',
            'form.user_id.required'                => '经办人信息不能为空',
            'form.user_id.exists'                  => '系统后台没有找到经办人信息',
            'form.warehouse_id.required'           => '进货仓库信息不能为空',
            'form.warehouse_id.exists'             => '系统后台没有找到进货仓库信息',
            'form.supplier_id.required'            => '供应商输入错误!',
            'form.supplier_id.exists'              => '没有找到供应商信息!',
            'detail.required'                      => '[进货明细]不能为空',
            'detail.*.batch_code.required'         => '商品批号不能为空',
            'detail.*.batch_code.unique'           => '批号已存在!',
            'detail.*.sncode.unique'               => 'SN码不能重复',
            'detail.*.expiry_date.date_format'     => '[过期时间]格式错误!',
            'detail.*.production_date.date_format' => '[生产日期]格式错误!',
        ];
    }

    public function formData(): array
    {
        return [
            'date'           => $this->input('form.date'),
            'status'         => 1, // 待审核
            'warehouse_id'   => $this->input('form.warehouse_id'),
            'type_id'        => $this->input('form.type_id'),
            'user_id'        => $this->input('form.user_id'),
            'supplier_id'    => $this->input('form.supplier_id'),
            'supplier_name'  => get_supplier_name($this->input('form.supplier_id')),
            'amount'         => collect($this->input('detail'))->sum('amount'),
            'create_user_id' => user()->id,
            'key'            => 'RKD' . date('Ymd') . str_pad((Purchase::today()->count() + 1), 4, '0', STR_PAD_LEFT),
            'remark'         => $this->input('form.remark')
        ];
    }

    /**
     * 明细表
     * @param $purchase
     * @return array
     */
    public function detailData($purchase): array
    {
        $data    = [];
        $details = $this->input('detail');

        foreach ($details as $k => $v) {
            $data[] = [
                'key'               => $purchase->key,
                'date'              => $purchase->date,
                'supplier_id'       => $purchase->supplier_id,
                'status'            => $purchase->status,
                'purchase_id'       => $purchase->id,
                'warehouse_id'      => $purchase->warehouse_id,
                'goods_id'          => $v['goods_id'],
                'goods_name'        => $v['goods_name'],
                'specs'             => $v['specs'],
                'price'             => $v['price'],
                'number'            => $v['number'],
                'unit_id'           => $v['unit_id'],
                'unit_name'         => $v['unit_name'],
                'amount'            => $v['amount'],
                'manufacturer_id'   => $v['manufacturer_id'] ?? null,
                'manufacturer_name' => $v['manufacturer_name'] ?? null,
                'production_date'   => $v['production_date'] ?? null,
                'expiry_date'       => $v['expiry_date'] ?? null,
                'batch_code'        => $v['batch_code'] ?? null,
                'sncode'            => $v['sncode'] ?? null,
                'remark'            => $v['remark'] ?? null,
                'approval_number'   => $v['approval_number'] ?? null,
            ];
        }

        return $data;
    }
}
