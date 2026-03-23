<?php

namespace App\Http\Requests\Web;

use App\Models\InventoryBatchs;
use App\Models\InventoryCheck;
use App\Rules\Web\SceneRule;
use Illuminate\Foundation\Http\FormRequest;

class InventoryCheckRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return match (request()->route()->getActionMethod()) {
            'manage' => $this->getManageRules(),
            'create' => $this->getCreateRules(),
            'update' => $this->getUpdateRules(),
            'check' => $this->getCheckRules(),
            'remove' => $this->getRemoveRules(),
            default => [],
        };
    }

    public function messages(): array
    {
        return match (request()->route()->getActionMethod()) {
            'manage' => $this->getManageMessages(),
            'create' => $this->getCreateMessages(),
            'update' => $this->getUpdateMessages(),
            'check' => $this->getCheckMessages(),
            'remove' => $this->getRemoveMessages(),
            default => [],
        };
    }

    private function getManageRules(): array
    {
        return [
            'filters' => [
                'nullable',
                'array',
                new SceneRule('InventoryCheckIndex'),
            ],
        ];
    }

    private function getManageMessages(): array
    {
        return [
            'filters.array' => '参数错误',
        ];
    }

    private function getCreateRules(): array
    {
        return $this->getWriteRules();
    }

    private function getCreateMessages(): array
    {
        return [
            'detail.required' => '[盘点明细]不能为空!',
        ];
    }

    private function getUpdateRules(): array
    {
        return [
            'id' => 'required|exists:inventory_checks,id,status,1',
            ...$this->getWriteRules(),
        ];
    }

    private function getWriteRules(): array
    {
        return [
            'form' => 'required|array',
            'form.date' => 'required|date_format:Y-m-d',
            'form.warehouse_id' => 'required|exists:warehouse,id',
            'form.department_id' => 'required|exists:department,id',
            'form.user_id' => 'required|exists:users,id',
            'detail' => 'required|array',
            'detail.*.goods_id' => 'required|exists:goods,id',
            'detail.*.goods_name' => 'required',
            'detail.*.specs' => 'nullable',
            'detail.*.manufacturer_id' => 'nullable|exists:manufacturer,id',
            'detail.*.manufacturer_name' => 'nullable',
            'detail.*.inventory_batchs_id' => [
                'nullable',
                'integer',
                'min:1',
                function ($attribute, $value, $fail) {
                    if ($this->isEmptyBatchId($value)) {
                        return;
                    }

                    $batch = InventoryBatchs::query()->find((int) $value);
                    $goodsName = (string) $this->input(str_replace('inventory_batchs_id', 'goods_name', $attribute), '');
                    $goodsId = (int) $this->input(str_replace('inventory_batchs_id', 'goods_id', $attribute), 0);
                    $warehouseId = (int) $this->input('form.warehouse_id', 0);

                    if (! $batch) {
                        $fail("[{$goodsName}]库存批次不存在!");

                        return;
                    }

                    if ((int) $batch->goods_id !== $goodsId) {
                        $fail("[{$goodsName}]库存批次商品不匹配!");
                    }

                    if ((int) $batch->warehouse_id !== $warehouseId) {
                        $fail("[{$goodsName}]库存批次仓库不匹配!");
                    }
                },
                function ($attribute, $value, $fail) {
                    if ($this->isEmptyBatchId($value)) {
                        return;
                    }

                    $goodsName = (string) $this->input(str_replace('inventory_batchs_id', 'goods_name', $attribute), '');
                    $batchCode = (string) $this->input(str_replace('inventory_batchs_id', 'batch_code', $attribute), '');
                    $duplicateCount = collect($this->input('detail', []))
                        ->filter(function (array $item) use ($value) {
                            $batchId = $item['inventory_batchs_id'] ?? null;

                            return ! $this->isEmptyBatchId($batchId)
                                && (int) $batchId === (int) $value;
                        })
                        ->count();

                    if ($duplicateCount > 1) {
                        $fail("[{$goodsName}]批次[{$batchCode}]不能重复!");
                    }
                },
                function ($attribute, $value, $fail) {
                    if (! $this->isEmptyBatchId($value)) {
                        return;
                    }

                    $goodsName = (string) $this->input(str_replace('inventory_batchs_id', 'goods_name', $attribute), '');
                    $diffNumber = (float) $this->input(str_replace('inventory_batchs_id', 'diff_number', $attribute), 0);
                    if ($diffNumber <= 0) {
                        $fail("[{$goodsName}]新批次仅允许盘盈明细!");
                    }
                },
            ],
            'detail.*.batch_code' => [
                'required',
                'string',
                function ($attribute, $value, $fail) {
                    $inventoryBatchId = $this->input(str_replace('batch_code', 'inventory_batchs_id', $attribute));
                    if (! $this->isEmptyBatchId($inventoryBatchId)) {
                        return;
                    }

                    $goodsId = (int) $this->input(str_replace('batch_code', 'goods_id', $attribute), 0);
                    $goodsName = (string) $this->input(str_replace('batch_code', 'goods_name', $attribute), '');
                    $batchCode = (string) ($value ?? '');
                    $duplicateCount = collect($this->input('detail', []))
                        ->filter(function (array $item) use ($goodsId, $batchCode) {
                            $batchId = $item['inventory_batchs_id'] ?? null;

                            return $this->isEmptyBatchId($batchId)
                                && (int) ($item['goods_id'] ?? 0) === $goodsId
                                && (string) ($item['batch_code'] ?? '') === $batchCode;
                        })
                        ->count();

                    if ($duplicateCount > 1) {
                        $fail("[{$goodsName}]批号[{$batchCode}]不能重复!");
                    }
                },
            ],
            'detail.*.production_date' => 'nullable|date_format:Y-m-d',
            'detail.*.expiry_date' => 'nullable|date_format:Y-m-d',
            'detail.*.sncode' => 'nullable|string',
            'detail.*.unit_id' => 'nullable|exists:unit,id',
            'detail.*.unit_name' => 'nullable',
            'detail.*.book_number' => 'required|numeric',
            'detail.*.actual_number' => 'required|numeric',
            'detail.*.diff_number' => 'required|numeric',
            'detail.*.price' => 'required|numeric',
            'detail.*.diff_amount' => 'required|numeric',
        ];
    }

    private function getUpdateMessages(): array
    {
        return [
            'id.required' => '缺少id参数!',
            'id.exists' => '没有找到数据!',
            'detail.required' => '[盘点明细]不能为空!',
        ];
    }

    private function getCheckRules(): array
    {
        return [
            'id' => 'required|exists:inventory_checks,id',
        ];
    }

    private function getCheckMessages(): array
    {
        return [
            'id.required' => 'id参数不能为空!',
            'id.exists' => '没有找到盘点单据!',
        ];
    }

    private function getRemoveRules(): array
    {
        return [
            'id' => 'required|exists:inventory_checks,id,status,1',
        ];
    }

    private function getRemoveMessages(): array
    {
        return [
            'id.required' => '缺少id参数',
            'id.exists' => '状态错误,无法删除!',
        ];
    }

    public function formData(): array
    {
        $data = [
            'date' => $this->input('form.date'),
            'warehouse_id' => $this->input('form.warehouse_id'),
            'department_id' => $this->input('form.department_id'),
            'user_id' => $this->input('form.user_id'),
            'remark' => $this->input('form.remark') ?? '',
            'create_user_id' => user()->id,
        ];

        if (request()->route()->getActionMethod() === 'create') {
            $data['key'] = 'IC'.date('Ymd').str_pad((InventoryCheck::query()->whereDate('created_at', now()->toDateString())->count() + 1), 4, '0', STR_PAD_LEFT);
            $data['status'] = 1;
        }

        return $data;
    }

    public function detailData($check): array
    {
        $data = [];
        $details = $this->input('detail', []);

        foreach ($details as $detail) {
            $data[] = [
                'key' => $check->key,
                'date' => $check->date,
                'warehouse_id' => $check->warehouse_id,
                'goods_id' => $detail['goods_id'],
                'goods_name' => $detail['goods_name'],
                'specs' => $detail['specs'] ?? null,
                'manufacturer_id' => $detail['manufacturer_id'] ?? null,
                'manufacturer_name' => $detail['manufacturer_name'] ?? null,
                'inventory_batchs_id' => $detail['inventory_batchs_id'] ?? null,
                'batch_code' => $detail['batch_code'] ?? null,
                'production_date' => $detail['production_date'] ?? null,
                'expiry_date' => $detail['expiry_date'] ?? null,
                'sncode' => $detail['sncode'] ?? null,
                'unit_id' => $detail['unit_id'] ?? null,
                'unit_name' => $detail['unit_name'] ?? null,
                'book_number' => $detail['book_number'],
                'actual_number' => $detail['actual_number'],
                'diff_number' => $detail['diff_number'],
                'price' => $detail['price'],
                'diff_amount' => $detail['diff_amount'],
                'remark' => $detail['remark'] ?? null,
                'status' => 1,
            ];
        }

        return $data;
    }

    private function isEmptyBatchId(mixed $value): bool
    {
        return $value === null || $value === '';
    }
}
