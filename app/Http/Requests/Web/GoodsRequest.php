<?php

namespace App\Http\Requests\Web;

use App\Models\Goods;
use App\Models\GoodsUnit;
use App\Models\PurchaseDetail;
use Illuminate\Support\Collection;
use Illuminate\Foundation\Http\FormRequest;

class GoodsRequest extends FormRequest
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

    public function rules(): array
    {
        return match (request()->route()->getActionMethod()) {
            'create' => $this->getCreateRules(),
            'update' => $this->getUpdateRules(),
            'remove' => $this->getRemoveRules(),
            'enable' => $this->getEnableRules(),
            'disable' => $this->getDisableRules(),
            'queryBatchs' => $this->getQueryBatchsRules(),
            'inventory',
            'inventoryBatch',
            'inventoryDetail' => $this->getInventoryBatchRules(),
            default => []
        };
    }

    public function messages(): array
    {
        return match (request()->route()->getActionMethod()) {
            'create' => $this->getCreateMessages(),
            'update' => $this->getUpdateMessages(),
            'remove' => $this->getRemoveMessages(),
            'enable' => $this->getEnableMessages(),
            'disable' => $this->getDisableMessages(),
            'queryBatchs' => $this->getQueryBatchsMessages(),
            'inventory',
            'inventoryBatch',
            'inventoryDetail' => $this->getInventoryBatchMessages(),
            default => []
        };
    }

    private function getInventoryBatchRules(): array
    {
        return [
            'goods_id' => 'required|integer|exists:goods,id',
        ];
    }

    private function getInventoryBatchMessages(): array
    {
        return [
            'goods_id.required' => '商品ID不能为空',
            'goods_id.integer'  => '商品ID必须是整数',
            'goods_id.exists'   => '商品ID不存在',
        ];
    }

    private function getRemoveRules(): array
    {
        return [
            'ids' => [
                'required',
                function ($attribute, $ids, $fail) {
                    $ids = explode(',', $ids);

                    // 验证删除数量与基础数据是否一致
                    if (count($ids) !== Goods::query()->whereIn('id', $ids)->count()) {
                        $fail('请求删除数据与数据库不一致!');
                        return;
                    }

                    // 进货入库使用了物品
                    $purchaseDetail = PurchaseDetail::query()->whereIn('goods_id', $ids)->first();
                    if ($purchaseDetail) {
                        $fail("进货入库单已使用[{$purchaseDetail->goods_name}]无法删除!");
                        return;
                    }

                    // 其他验证?通过逻辑判断,如果没有入库就没有库存,因此其他业务无法出库,觉得暂时不需要验证
                }
            ]
        ];
    }

    private function getRemoveMessages(): array
    {
        return [
            'id.required' => '缺少id参数!',
            'id.exists'   => '找不到商品信息'
        ];
    }

    private function getCreateRules(): array
    {
        return [
            'goods'                     => 'required|array',
            'goods.name'                => 'required',
            'goods.type_id'             => 'required|exists:goods_type,id',
            'goods.expense_category_id' => 'required|exists:expense_category,id',
            'goods.max'                 => 'required|integer',
            'goods.min'                 => 'required|integer|lte:goods.max',
            'goods.warn_days'           => 'required|integer|min:0',
            'units'                     => 'required|array',
            'units.*.barcode'           => 'nullable|unique:goods_unit,barcode',
            'alarm'                     => 'required|array',
            'alarm.*.max'               => 'required|integer',
            'alarm.*.min'               => 'required|integer|lte:alarm.*.max',
        ];
    }

    private function getCreateMessages(): array
    {
        return [
            'goods.name.required'                => '商品名称不能为空!',
            'goods.type_id.required'             => '项目分类不能为空!',
            'goods.expense_category_id.required' => '费用类别不能为空',
            'goods.expense_category_id.exists'   => '费用类别不存在',
            'goods.min.lte'                      => '合计预警[库存下限]必须小于或等于[库存上限]',
            'goods.warn_days.required'           => '[过期预警]不能为空!',
            'goods.warn_days.min'                => '[过期预警]不能小于0!',
        ];
    }

    private function getUpdateRules(): array
    {
        return [
            'id'                        => [
                'required',
                'exists:goods',
                function ($attribute, $id, $fail) {
                    // 已使用的计量单位,无法删除
                    $oldUnitId = GoodsUnit::query()->where('goods_id', $id)->get()->pluck('unit_id');
                    $newUnitId = collect($this->input('units'))->pluck('unit_id');
                    $removeIds = $oldUnitId->diff($newUnitId)->values()->toArray();
                    $unit_name = count($removeIds) ? get_unit_name($removeIds[0]) : null;
                    if (PurchaseDetail::query()->where('goods_id', $id)->whereIn('unit_id', $removeIds)->count()) {
                        $fail("[{$unit_name}]已经在使用中,无法删除!");
                        return;
                    }
                }
            ],
            'goods'                     => 'required|array',
            'goods.name'                => 'required|string',
            'goods.short_name'          => 'required|string',
            'goods.expense_category_id' => 'required|exists:expense_category,id',
            'goods.max'                 => 'required|integer',
            'goods.min'                 => 'required|integer|lte:goods.max',
            'goods.warn_days'           => 'required|integer|min:0',
            'units'                     => 'required|array',
            'units.*.unit_id'           => 'required|distinct|exists:unit,id',
            'units.*.id'                => [
                'nullable',
                function ($attribute, $id, $fail) {
                    $unit_id   = $this->input(str_replace('id', 'unit_id', $attribute));
                    $goodsUnit = GoodsUnit::query()->find($id);

                    if (!$goodsUnit) {
                        $fail('goods_unit表没有找到id记录!');
                        return;
                    }

                    $used      = PurchaseDetail::query()->where('goods_id', $this->input('id'))->where('unit_id', $goodsUnit->unit_id)->count();
                    $unit_name = get_unit_name($goodsUnit->unit_id);

                    // 修改单位: 单位发生业务,无法修改
                    if ($goodsUnit->unit_id !== $unit_id && $used) {
                        $fail("单位【{$unit_name}】已发生业务,无法修改!");
                        return;
                    }
                }
            ],
            'alarm'                     => 'required|array',
            'alarm.*.max'               => 'required|integer',
            'alarm.*.min'               => 'required|integer|lte:alarm.*.max',
        ];
    }

    private function getUpdateMessages(): array
    {
        return [
            'id.required'                        => '缺少id参数',
            'id.exists'                          => '没有找到商品信息',
            'goods.name.required'                => '缺少商品名称',
            'goods.expense_category_id.required' => '费用类别不能为空',
            'goods.expense_category_id.exists'   => '费用类别不存在',
            'goods.min.lte'                      => '合计预警[库存下限]必须小于或等于[库存上限]',
            'goods.warn_days.required'           => '[过期预警]不能为空!',
            'goods.warn_days.min'                => '[过期预警]不能小于0!',
        ];
    }


    public function getGoodsData(): array
    {
        return [
            'name'                => $this->input('goods.name'),
            'short_name'          => $this->input('goods.short_name'),
            'type_id'             => $this->input('goods.type_id'),
            'expense_category_id' => $this->input('goods.expense_category_id'),
            'high_value'          => $this->input('goods.high_value', false),
            'is_drug'             => $this->input('goods.is_drug', false),
            'specs'               => $this->input('goods.specs'),
            'warn_days'           => $this->input('goods.warn_days'),
            'min'                 => $this->input('goods.min'),
            'max'                 => $this->input('goods.max'),
            'commission'          => $this->input('goods.commission', false),
            'integral'            => $this->input('goods.integral', false),
            'remark'              => $this->input('goods.remark'),
            'barcode'             => collect($this->input('units'))->implode('barcode', ','),
            'approval_number'     => $this->input('goods.approval_number'),
        ];
    }

    public function getGoodsUnit(): Collection
    {
        return collect($this->input('units'))->mapWithKeys(function ($v) {
            return [
                $v['unit_id'] => [
                    'basic'       => $v['basic'],
                    'prebuyprice' => $v['prebuyprice'],
                    'rate'        => $v['rate'],
                    'retailprice' => $v['retailprice'],
                    'barcode'     => $v['barcode'] ?? null
                ]
            ];
        });
    }

    public function getGoodsAlarm(): Collection
    {
        return collect($this->input('alarm'))->mapWithKeys(function ($v) {
            return [
                $v['warehouse_id'] => [
                    'max' => $v['max'],
                    'min' => $v['min']
                ]
            ];
        });
    }

    private function getEnableRules(): array
    {
        return [
            'ids' => 'required|array'
        ];
    }

    private function getEnableMessages(): array
    {
        return [
            'ids.required' => '请选择要启用的物品',
            'ids.array'    => '参数错误'
        ];
    }

    private function getDisableRules(): array
    {
        return [
            'ids' => 'required|array'
        ];
    }

    private function getDisableMessages(): array
    {
        return [
            'ids.required' => '请选择要禁用的物品',
            'ids.array'    => '参数错误'
        ];
    }

    private function getQueryBatchsRules(): array
    {
        return [
            'goods_id'     => 'required|exists:goods,id',
            'warehouse_id' => 'required|exists:warehouse,id'
        ];
    }

    private function getQueryBatchsMessages(): array
    {
        return [
            'goods_id.required'     => '商品ID不能为空',
            'goods_id.exists'       => '商品ID不存在',
            'warehouse_id.required' => '仓库ID不能为空',
            'warehouse_id.exists'   => '仓库ID不存在'
        ];
    }

    /**
     * 获取附件ID数组（用于 attachment_uses 多态关联）
     *
     * @return array
     */
    public function attachmentData(): array
    {
        $attachmentIds = [];

        foreach ($this->input('attachments', []) as $index => $attachment) {
            $attachmentId = $attachment['id'] ?? null;
            if ($attachmentId) {
                $attachmentIds[$attachmentId] = ['sort' => $index];
            }
        }

        return $attachmentIds;
    }
}
