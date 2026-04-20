<?php

namespace App\Http\Requests\Erkai;

use App\Enums\ErkaiStatus;
use App\Models\Goods;
use App\Models\GoodsUnit;
use App\Rules\Web\SceneRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ErkaiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return match (request()->route()->getActionMethod()) {
            'manage' => $this->getManageRules(),
            'info' => $this->getInfoRules(),
            'create' => $this->getCreateRules(),
            'update' => $this->getUpdateRules(),
            default => []
        };
    }

    public function messages(): array
    {
        return match (request()->route()->getActionMethod()) {
            'info' => $this->getInfoMessages(),
            'create' => $this->getCreateMessages(),
            'update' => $this->getUpdateMessages(),
            default => []
        };
    }

    private function getManageRules(): array
    {
        return [
            'rows' => 'nullable|integer|min:1|max:1000',
            'page' => 'nullable|integer|min:1',
            'keyword' => 'nullable|string|max:255',
            'date' => 'required|array|size:2',
            'date.*' => 'required|date|date_format:Y-m-d',
            'sort' => [
                'nullable',
                'string',
                Rule::in([
                    'id',
                    'customer_id',
                    'department_id',
                    'type',
                    'status',
                    'medium_id',
                    'payable',
                    'income',
                    'deposit',
                    'coupon',
                    'arrearage',
                    'user_id',
                    'created_at',
                    'updated_at',
                ]),
            ],
            'order' => 'nullable|string|in:asc,desc',
            'filters' => ['nullable', 'array', new SceneRule('ErkaiIndex')],
            'filters.*' => 'required|array',
            'filters.*.field' => 'required|string',
            'filters.*.operator' => 'required|string',
            'filters.*.value' => 'nullable',
        ];
    }

    private function getInfoRules(): array
    {
        return [
            'id' => 'required|exists:erkai,id',
        ];
    }

    private function getCreateRules(): array
    {
        return [
            'customer_id' => 'required|exists:customer,id',
            'form' => 'required|array',
            'form.department_id' => 'required|exists:department,id',
            'form.type' => 'required|numeric',
            'form.medium_id' => 'required|exists:medium,id',
            'detail' => 'required|array',
            'detail.*.type' => 'required|in:goods,product',
            'detail.*.package_id' => 'nullable|exists:product_package,id',
            'detail.*.product_id' => [
                'nullable',
                'exists:product,id',
                'required_without:detail.*.goods_id',
            ],
            'detail.*.goods_id' => [
                'nullable',
                'exists:goods,id',
                'required_without:detail.*.product_id',
                function ($attribute, $value, $fail) {
                    $times = $this->input(str_replace('goods_id', 'times', $attribute));
                    $unitId = $this->input(str_replace('goods_id', 'unit_id', $attribute));
                    $goodsName = $this->input(str_replace('goods_id', 'goods_name', $attribute));

                    if (collect($this->input('detail'))->where('goods_id', $value)->count() > 1) {
                        $fail("[{$goodsName}]不能重复!");

                        return;
                    }

                    $goods = Goods::query()->find($value);
                    $currentUnit = GoodsUnit::query()->where('goods_id', $value)->where('unit_id', $unitId)->first();
                    $amount = bcmul($times, $currentUnit?->rate ?? 0, 4);

                    if ($goods && $amount > $goods->inventory_number) {
                        $fail("[{$goodsName}]库存数量不足!");
                    }
                },
            ],
        ];
    }

    private function getUpdateRules(): array
    {
        $rules = $this->getCreateRules();
        $rules['id'] = 'required|exists:erkai,id,status,'.ErkaiStatus::CANCELLED->value;

        return $rules;
    }

    private function getInfoMessages(): array
    {
        return [
            'id.required' => 'id不能为空!',
            'id.exists' => '数据不存在!',
        ];
    }

    private function getCreateMessages(): array
    {
        return [
            'detail.required' => '开单信息不能为空!',
        ];
    }

    private function getUpdateMessages(): array
    {
        return [
            'id.exists' => '业务状态错误！',
            'detail.*.product_id.required_without' => '[项目信息]不能为空!',
            'detail.*.goods_id.required_without' => '[商品信息]不能为空!',
        ];
    }

    public function formData(): array
    {
        $data = [
            'department_id' => $this->input('form.department_id'),
            'type' => $this->input('form.type'),
            'status' => ErkaiStatus::PENDING_CHARGE,
            'payable' => collect($this->input('detail'))->sum('payable'),
            'income' => 0,
            'deposit' => 0,
            'arrearage' => 0,
            'medium_id' => $this->input('form.medium_id'),
            'remark' => $this->input('form.remark'),
            'user_id' => user()->id,
        ];

        if (request()->route()->getActionMethod() === 'create') {
            $data['customer_id'] = $this->input('customer_id');
        }

        return $data;
    }

    public function detailData($erkai): array
    {
        $details = $this->input('detail');
        $data = [];

        foreach ($details as $detail) {
            $data[] = [
                'customer_id' => $erkai->customer_id,
                'status' => 2,
                'type' => $detail['type'],
                'package_id' => $detail['package_id'] ?? null,
                'package_name' => $detail['package_name'] ?? null,
                'splitable' => $detail['splitable'] ?? null,
                'product_id' => $detail['product_id'] ?? null,
                'product_name' => $detail['product_name'] ?? null,
                'goods_id' => $detail['goods_id'] ?? null,
                'goods_name' => $detail['goods_name'] ?? null,
                'times' => $detail['times'],
                'unit_id' => $detail['unit_id'] ?? null,
                'unit_name' => isset($detail['unit_id']) ? get_unit_name($detail['unit_id']) : null,
                'specs' => $detail['specs'] ?? null,
                'price' => $detail['price'],
                'sales_price' => $detail['sales_price'],
                'payable' => $detail['payable'],
                'amount' => 0,
                'department_id' => $detail['department_id'],
                'salesman' => $detail['salesman'],
                'remark' => $detail['remark'] ?? null,
                'user_id' => user()->id,
            ];
        }

        return $data;
    }

    public function cashierData($erkai): array
    {
        return [
            'customer_id' => $erkai->customer_id,
            'detail' => $erkai->details,
            'payable' => $erkai->details->sum('payable'),
            'income' => 0,
            'arrearage' => 0,
            'coupon' => 0,
            'status' => 1,
            'user_id' => user()->id,
        ];
    }
}
