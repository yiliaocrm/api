<?php

namespace App\Http\Requests\Web;

use App\Enums\CashierInvoiceType;
use App\Models\CashierInvoice;
use App\Rules\Web\SceneRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CashierInvoiceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return match (request()->route()->getActionMethod()) {
            default => [],
            'index' => $this->getManageRules(),
            'customerProduct', 'customerGoods' => [
                'customer_id' => 'required|exists:customer,id',
            ],
            'info' => [
                'id' => 'required|exists:cashier_invoices,id',
            ],
            'create' => [
                'customer_id' => 'required|exists:customer,id',
                'form' => 'required|array',
                'form.date' => 'required|date_format:Y-m-d',
                'form.type' => ['required', Rule::in(array_keys(CashierInvoiceType::options()))],
                'grid' => 'required|array|min:1',
                'grid.*.cashier_id' => 'required|exists:cashier,id',
                'grid.*.name' => 'required|string',
                'grid.*.times' => 'required|integer|min:1',
                'grid.*.customer_goods_id' => 'nullable|exists:customer_goods,id',
                'grid.*.customer_product_id' => 'nullable|exists:customer_product,id',
                'grid.*.product_id' => 'nullable|exists:product,id',
                'grid.*.invoice_amount' => 'required|numeric|min:0.01',
                'grid.*.income' => 'required|numeric',
                'grid.*.deposit' => 'required|numeric',
            ],
            'update' => [
                'id' => 'required|exists:cashier_invoices,id',
                'form' => 'required|array',
                'form.date' => 'required|date_format:Y-m-d',
                'form.type' => ['required', Rule::in(array_keys(CashierInvoiceType::options()))],
                'grid' => 'required|array|min:1',
                'grid.*.cashier_id' => 'required|exists:cashier,id',
                'grid.*.name' => 'required|string',
                'grid.*.times' => 'required|integer|min:1',
                'grid.*.customer_goods_id' => 'nullable|exists:customer_goods,id',
                'grid.*.customer_product_id' => 'nullable|exists:customer_product,id',
                'grid.*.product_id' => 'nullable|exists:product,id',
                'grid.*.invoice_amount' => 'required|numeric|min:0.01',
                'grid.*.income' => 'required|numeric',
                'grid.*.deposit' => 'required|numeric',
            ],
        };
    }

    public function messages(): array
    {
        return match (request()->route()->getActionMethod()) {
            default => [],
            'index' => $this->getManageMessages(),
            'customerProduct', 'customerGoods' => [
                'customer_id.required' => '顾客id不能为空!',
                'customer_id.exists' => '顾客信息不存在!',
            ],
            'info' => [
                'id.required' => '开票id不能为空!',
                'id.exists' => '开票信息不存在!',
            ],
            'create' => [
                'customer_id.required' => '顾客id不能为空!',
                'customer_id.exists' => '顾客信息不存在!',
                'form.required' => '开票表单数据不能为空!',
                'form.array' => '开票表单数据格式错误!',
                'form.date.required' => '开票日期不能为空!',
                'form.date.date_format' => '开票日期格式错误!',
                'form.type.required' => '开票类型不能为空!',
                'form.type.in' => '开票类型不正确!',
                'grid.required' => '开票明细数据不能为空!',
                'grid.array' => '开票明细数据格式错误!',
                'grid.min' => '开票明细数据不能为空!',
                'grid.*.cashier_id.required' => '收费单ID不能为空!',
                'grid.*.cashier_id.exists' => '收费单信息不存在!',
                'grid.*.name.required' => '开票名称不能为空!',
                'grid.*.name.string' => '开票名称格式错误!',
                'grid.*.times.required' => '开票数量不能为空!',
                'grid.*.times.integer' => '开票数量必须为整数!',
                'grid.*.times.min' => '开票数量不能小于1!',
                'grid.*.customer_goods_id.exists' => '已购物品信息不存在!',
                'grid.*.customer_product_id.exists' => '已购项目信息不存在!',
                'grid.*.product_id.exists' => '商品信息不存在!',
                'grid.*.invoice_amount.required' => '开票金额不能为空!',
                'grid.*.invoice_amount.numeric' => '开票金额必须为数字!',
                'grid.*.invoice_amount.min' => '开票金额不能小于0.01元!',
                'grid.*.income.numeric' => '实收金额必须为数字!',
                'grid.*.deposit.numeric' => '余额支付必须为数字!',
            ],
            'update' => [
                'id.required' => '开票id不能为空!',
                'id.exists' => '开票信息不存在!',
                'form.required' => '开票表单数据不能为空!',
                'form.array' => '开票表单数据格式错误!',
                'form.date.required' => '开票日期不能为空!',
                'form.date.date_format' => '开票日期格式错误!',
                'form.type.required' => '开票类型不能为空!',
                'form.type.in' => '开票类型不正确!',
                'grid.required' => '开票明细数据不能为空!',
                'grid.array' => '开票明细数据格式错误!',
                'grid.min' => '开票明细数据不能为空!',
                'grid.*.cashier_id.required' => '收费单ID不能为空!',
                'grid.*.cashier_id.exists' => '收费单信息不存在!',
                'grid.*.name.required' => '开票名称不能为空!',
                'grid.*.name.string' => '开票名称格式错误!',
                'grid.*.times.required' => '开票数量不能为空!',
                'grid.*.times.integer' => '开票数量必须为整数!',
                'grid.*.times.min' => '开票数量不能小于1!',
                'grid.*.customer_goods_id.exists' => '已购物品信息不存在!',
                'grid.*.customer_product_id.exists' => '已购项目信息不存在!',
                'grid.*.product_id.exists' => '商品信息不存在!',
                'grid.*.invoice_amount.required' => '开票金额不能为空!',
                'grid.*.invoice_amount.numeric' => '开票金额必须为数字!',
                'grid.*.invoice_amount.min' => '开票金额不能小于0.01元!',
                'grid.*.income.numeric' => '实收金额必须为数字!',
                'grid.*.deposit.numeric' => '余额支付必须为数字!',
            ],
        };
    }

    protected function getManageRules(): array
    {
        return [
            'sort' => 'nullable|string|max:255',
            'order' => 'nullable|string|in:asc,desc',
            'rows' => 'nullable|integer|min:1|max:1000',
            'filters' => [
                'nullable',
                'array',
                new SceneRule('CashierInvoiceIndex'),
            ],
            'filters.*' => ['required', 'array'],
            'filters.*.field' => 'required|string',
            'filters.*.operator' => 'required|string',
            'keyword' => 'nullable|string',
        ];
    }

    protected function getManageMessages(): array
    {
        return [
            'sort.string' => '[排序字段]格式不正确',
            'sort.max' => '[排序字段]不能超过255个字符',
            'order.string' => '[排序方式]格式不正确',
            'order.in' => '[排序方式]只能是asc或desc',
            'rows.integer' => '[每页数量]必须为整数',
            'rows.min' => '[每页数量]至少为1',
            'rows.max' => '[每页数量]不能超过1000',
            'filters.array' => '[筛选条件]格式不正确',
            'filters.*.required' => '[筛选条件]格式不正确',
            'filters.*.array' => '[筛选条件]格式不正确',
            'filters.*.field.required' => '[筛选字段]不能为空',
            'filters.*.field.string' => '[筛选字段]格式不正确',
            'filters.*.operator.required' => '[筛选操作符]不能为空',
            'filters.*.operator.string' => '[筛选操作符]格式不正确',
        ];
    }

    /**
     * 创建开票表单数据
     */
    public function getInvoiceCreateData(): array
    {
        $date = $this->input('form.date');
        $key = $this->generateInvoiceKey($date);

        $data = [
            'customer_id' => $this->input('customer_id'),
            'type' => $this->input('form.type'),
            'key' => $key,
            'date' => $date,
            'code' => $this->input('form.code'),
            'number' => $this->input('form.number'),
            'tax_number' => $this->input('form.tax_number'),
            'title' => $this->input('form.title'),
            'bank_name' => $this->input('form.bank_name'),
            'bank_account' => $this->input('form.bank_account'),
            'remark' => $this->input('form.remark'),
            'amount' => array_sum(array_column($this->input('grid'), 'invoice_amount')),
            'create_user_id' => user()->id,
        ];

        // 更新
        if ($this->route()->getActionMethod() === 'update') {
            unset($data['customer_id'], $data['key'], $data['create_user_id']);
        }

        return $data;
    }

    protected function generateInvoiceKey(string $date): string
    {
        $prefix = 'KP'.date('Ymd', strtotime($date));
        $maxSerial = CashierInvoice::query()
            ->whereDate('date', $date)
            ->where('key', 'like', $prefix.'%')
            ->pluck('key')
            ->reduce(function (int $carry, string $key) use ($prefix): int {
                $serial = (int) substr($key, strlen($prefix));

                return max($carry, $serial);
            }, 0);

        $serial = $maxSerial + 1;
        $retry = 0;
        while ($retry < 100) {
            $candidate = $prefix.str_pad((string) $serial, 4, '0', STR_PAD_LEFT);
            $exists = CashierInvoice::query()
                ->whereDate('date', $date)
                ->where('key', $candidate)
                ->exists();

            if (! $exists) {
                return $candidate;
            }

            $serial++;
            $retry++;
        }

        return $prefix.str_pad((string) $serial, 4, '0', STR_PAD_LEFT);
    }

    /**
     * 创建开票明细数据
     */
    public function getInvoiceDetailData(int $cashier_invoice_id, string $customer_id): array
    {
        $grid = $this->input('grid');
        $data = [];
        foreach ($grid as $item) {
            $data[] = [
                'cashier_invoice_id' => $cashier_invoice_id,
                'customer_id' => $customer_id,
                'cashier_id' => $item['cashier_id'],
                'customer_goods_id' => $item['customer_goods_id'] ?? null,
                'customer_product_id' => $item['customer_product_id'] ?? null,
                'package_id' => $item['package_id'] ?? null,
                'package_name' => $item['package_name'] ?? null,
                'product_id' => $item['product_id'] ?? null,
                'product_name' => $item['product_name'] ?? null,
                'goods_id' => $item['goods_id'] ?? null,
                'goods_name' => $item['goods_name'] ?? null,
                'name' => $item['name'],
                'times' => $item['times'],
                'unit_id' => $item['unit_id'] ?? null,
                'unit_name' => $item['unit_name'] ?? null,
                'specs' => $item['specs'] ?? null,
                'invoice_amount' => $item['invoice_amount'],
                'income' => $item['income'],
                'deposit' => $item['deposit'],
            ];
        }

        return $data;
    }
}
