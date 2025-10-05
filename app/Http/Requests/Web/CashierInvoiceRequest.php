<?php

namespace App\Http\Requests\Web;

use App\Models\CashierInvoice;
use Illuminate\Foundation\Http\FormRequest;

class CashierInvoiceRequest extends FormRequest
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
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return match (request()->route()->getActionMethod()) {
            default => [],
            'customerProduct', 'customerGoods' => [
                'customer_id' => 'required|exists:customer,id'
            ],
            'info' => [
                'id' => 'required|exists:cashier_invoices,id'
            ],
            'create' => [
                'customer_id'                => 'required|exists:customer,id',
                'form'                       => 'required|array',
                'form.date'                  => 'required|date_format:Y-m-d',
                'form.type'                  => 'required',
                'grid'                       => 'required|array',
                'grid.*.cashier_id'          => 'required|exists:cashier,id',
                'grid.*.customer_goods_id'   => 'nullable|exists:customer_goods,id',
                'grid.*.customer_product_id' => 'nullable|exists:customer_product,id',
                'grid.*.product_id'          => 'nullable|exists:product,id',
                'grid.*.invoice_amount'      => 'required|numeric|min:0.01',
                'grid.*.income'              => 'required',
                'grid.*.deposit'             => 'required'
            ],
            'update' => [
                'id'                         => 'required|exists:cashier_invoices,id',
                'form'                       => 'required|array',
                'form.date'                  => 'required|date_format:Y-m-d',
                'form.type'                  => 'required',
                'grid'                       => 'required|array',
                'grid.*.cashier_id'          => 'required|exists:cashier,id',
                'grid.*.customer_goods_id'   => 'nullable|exists:customer_goods,id',
                'grid.*.customer_product_id' => 'nullable|exists:customer_product,id',
                'grid.*.product_id'          => 'nullable|exists:product,id',
                'grid.*.invoice_amount'      => 'required|numeric|min:0.01',
                'grid.*.income'              => 'required',
                'grid.*.deposit'             => 'required'
            ],
        };
    }

    public function messages(): array
    {
        return match (request()->route()->getActionMethod()) {
            default => [],
            'customerProduct', 'customerGoods' => [
                'customer_id.required' => '顾客id不能为空!',
                'customer_id.exists'   => '顾客信息不存在!'
            ],
            'info' => [
                'id.required' => '开票id不能为空!',
                'id.exists'   => '开票信息不存在!'
            ],
            'create' => [
                'customer_id.required'              => '顾客id不能为空!',
                'customer_id.exists'                => '顾客信息不存在!',
                'form.required'                     => '开票表单数据不能为空!',
                'form.array'                        => '开票表单数据格式错误!',
                'form.date.required'                => '开票日期不能为空!',
                'form.date.date_format'             => '开票日期格式错误!',
                'form.type.required'                => '开票类型不能为空!',
                'grid.required'                     => '开票明细数据不能为空!',
                'grid.array'                        => '开票明细数据格式错误!',
                'grid.*.cashier_id.required'        => '收费单ID不能为空!',
                'grid.*.cashier_id.exists'          => '收费单信息不存在!',
                'grid.*.customer_goods_id.exists'   => '已购物品信息不存在!',
                'grid.*.customer_product_id.exists' => '已购项目信息不存在!',
                'grid.*.product_id.exists'          => '商品信息不存在!',
                'grid.*.invoice_amount.required'    => '开票金额不能为空!',
                'grid.*.invoice_amount.numeric'     => '开票金额必须为数字!',
                'grid.*.invoice_amount.min'         => '开票金额不能小于0.01元!',
            ],
            'update' => [
                'id.required'                       => '开票id不能为空!',
                'id.exists'                         => '开票信息不存在!',
                'form.required'                     => '开票表单数据不能为空!',
                'form.array'                        => '开票表单数据格式错误!',
                'form.date.required'                => '开票日期不能为空!',
                'form.date.date_format'             => '开票日期格式错误!',
                'form.type.required'                => '开票类型不能为空!',
                'grid.required'                     => '开票明细数据不能为空!',
                'grid.array'                        => '开票明细数据格式错误!',
                'grid.*.cashier_id.required'        => '收费单ID不能为空!',
                'grid.*.cashier_id.exists'          => '收费单信息不存在!',
                'grid.*.customer_goods_id.exists'   => '已购物品信息不存在!',
                'grid.*.customer_product_id.exists' => '已购项目信息不存在!',
                'grid.*.product_id.exists'          => '商品信息不存在!',
                'grid.*.invoice_amount.required'    => '开票金额不能为空!',
                'grid.*.invoice_amount.numeric'     => '开票金额必须为数字!',
                'grid.*.invoice_amount.min'         => '开票金额不能小于0.01元!',
            ],
        };
    }

    /**
     * 创建开票表单数据
     * @return array
     */
    public function getInvoiceCreateData(): array
    {
        $date = $this->input('form.date');

        // 获取当天的开票记录数，如果没有开票记录，count() 方法将返回0
        $count = CashierInvoice::query()->whereDate('created_at', $date)->count() + 1;

        // 生成流水号，前缀:KP后缀:年月日+4位流水号
        $key = 'KP' . date('Ymd', strtotime($date)) . str_pad($count, 4, '0', STR_PAD_LEFT);

        $data = [
            'customer_id'    => $this->input('customer_id'),
            'type'           => $this->input('form.type'),
            'key'            => $key,
            'date'           => $date,
            'code'           => $this->input('form.code'),
            'number'         => $this->input('form.number'),
            'tax_number'     => $this->input('form.tax_number'),
            'title'          => $this->input('form.title'),
            'bank_name'      => $this->input('form.bank_name'),
            'bank_account'   => $this->input('form.bank_account'),
            'remark'         => $this->input('form.remark'),
            'amount'         => array_sum(array_column($this->input('grid'), 'invoice_amount')),
            'create_user_id' => user()->id,
        ];

        // 更新
        if ($this->route()->getActionMethod() === 'update') {
            unset($data['customer_id'], $data['key']);
        }

        return $data;
    }

    /**
     * 创建开票明细数据
     * @param int $cashier_invoice_id
     * @param string $customer_id
     * @return array
     */
    public function getInvoiceDetailData(int $cashier_invoice_id, string $customer_id): array
    {
        $grid = $this->input('grid');
        $data = [];
        foreach ($grid as $item) {
            $data[] = [
                'cashier_invoice_id'  => $cashier_invoice_id,
                'customer_id'         => $customer_id,
                'cashier_id'          => $item['cashier_id'],
                'customer_goods_id'   => $item['customer_goods_id'] ?? null,
                'customer_product_id' => $item['customer_product_id'] ?? null,
                'package_id'          => $item['package_id'] ?? null,
                'package_name'        => $item['package_name'] ?? null,
                'product_id'          => $item['product_id'] ?? null,
                'product_name'        => $item['product_name'] ?? null,
                'goods_id'            => $item['goods_id'] ?? null,
                'goods_name'          => $item['goods_name'] ?? null,
                'name'                => $item['name'],
                'times'               => $item['times'],
                'unit_id'             => $item['unit_id'] ?? null,
                'unit_name'           => $item['unit_name'] ?? null,
                'specs'               => $item['specs'] ?? null,
                'invoice_amount'      => $item['invoice_amount'],
                'income'              => $item['income'],
                'deposit'             => $item['deposit'],
            ];
        }
        return $data;
    }

}
