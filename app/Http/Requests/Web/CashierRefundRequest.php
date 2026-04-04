<?php

namespace App\Http\Requests\Web;

use App\Enums\CashierRefundStatus;
use App\Models\CashierRefund;
use App\Models\Customer;
use App\Models\CustomerProduct;
use App\Rules\Web\SceneRule;
use Illuminate\Foundation\Http\FormRequest;

class CashierRefundRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return match (request()->route()->getActionMethod()) {
            'create' => $this->getCreateRules(),
            'manage' => $this->getManageRules(),
            'remove' => $this->getRemoveRules(),
            'products' => $this->getCandidateRules(),
            'goods' => $this->getCandidateRules(),
            default => [],
        };
    }

    public function messages(): array
    {
        return match (request()->route()->getActionMethod()) {
            'create' => $this->getCreateMessages(),
            'manage' => $this->getManageMessages(),
            'remove' => $this->getRemoveMessages(),
            'products' => $this->getCandidateMessages(),
            'goods' => $this->getCandidateMessages(),
            default => [],
        };
    }

    private function getCreateRules(): array
    {
        return [
            'customer_id' => 'required|exists:customer,id',
            'detail.*.product_id' => [
                'nullable',
                function (string $attribute, mixed $productId, \Closure $fail): void {
                    if ($productId == 1 && ! $this->input(str_replace('product_id', 'amount', $attribute))) {
                        $fail('[预收费用]退款金额不能为0!');

                        return;
                    }

                    $amount = collect($this->input('detail'))->where('product_id', 1)->sum('amount');
                    $customer = Customer::query()->find($this->input('customer_id'));

                    if ($customer && $customer->balance < $amount) {
                        $fail('[账户余额]不足!');
                    }
                },
            ],
            'detail.*.amount' => [
                'required',
                function (string $attribute, mixed $amount, \Closure $fail): void {
                    $productId = $this->input(str_replace('amount', 'product_id', $attribute));
                    $customerProductId = $this->input(str_replace('amount', 'customer_product_id', $attribute));

                    if (! $productId || $productId != 1 || ! $customerProductId) {
                        return;
                    }

                    $customerProduct = CustomerProduct::query()->find($customerProductId);
                    if ($customerProduct && $amount > $customerProduct->income) {
                        $fail('预收费用退款金额不能大于实收金额');
                    }
                },
            ],
        ];
    }

    private function getCreateMessages(): array
    {
        return [
            'customer_id.required' => '缺少customer_id参数',
            'customer_id.exists' => '没有找到顾客信息',
        ];
    }

    private function getManageRules(): array
    {
        return [
            'date' => 'required|array|size:2',
            'date.*' => 'required|date|date_format:Y-m-d',
            'sort' => 'nullable|string|max:255',
            'order' => 'nullable|string|in:asc,desc',
            'rows' => 'nullable|integer|min:1|max:1000',
            'filters' => [
                'nullable',
                'array',
                new SceneRule('CashierRefundIndex'),
            ],
            'filters.*' => ['required', 'array'],
            'filters.*.field' => 'required|string',
            'filters.*.operator' => 'required|string',
            'keyword' => 'nullable|string',
        ];
    }

    private function getManageMessages(): array
    {
        return [
            'date.required' => '[查询日期]不能为空',
            'date.array' => '[查询日期]格式不正确',
            'date.size' => '[查询日期]必须包含开始和结束日期',
            'date.*.required' => '[查询日期]格式不正确',
            'date.*.date' => '[查询日期]格式不正确',
            'date.*.date_format' => '[查询日期]格式必须为Y-m-d',
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

    private function getRemoveRules(): array
    {
        return [
            'id' => [
                'required',
                function (string $attribute, mixed $id, \Closure $fail): void {
                    $refund = CashierRefund::query()->find($id);

                    if (! $refund) {
                        $fail('没有找到退款单据!');

                        return;
                    }

                    if ($refund->status !== CashierRefundStatus::CANCELLED) {
                        $fail('不是[退单]状态,无法删除!');
                    }
                },
            ],
        ];
    }

    private function getRemoveMessages(): array
    {
        return [
            'id.required' => '缺少id参数',
        ];
    }

    private function getCandidateRules(): array
    {
        return [
            'customer_id' => 'required|exists:customer,id',
            'sort' => 'nullable|string|max:255',
            'order' => 'nullable|string|in:asc,desc',
            'rows' => 'nullable|integer|min:1|max:1000',
        ];
    }

    private function getCandidateMessages(): array
    {
        return [
            'customer_id.required' => '缺少customer_id参数',
            'customer_id.exists' => '没有找到顾客信息',
            'sort.string' => '[排序字段]格式不正确',
            'sort.max' => '[排序字段]不能超过255个字符',
            'order.string' => '[排序方式]格式不正确',
            'order.in' => '[排序方式]只能是asc或desc',
            'rows.integer' => '[每页数量]必须为整数',
            'rows.min' => '[每页数量]至少为1',
            'rows.max' => '[每页数量]不能超过1000',
        ];
    }

    public function formData(): array
    {
        return [
            'customer_id' => $this->input('customer_id'),
            'amount' => collect($this->input('detail'))->sum('amount'),
            'remark' => null,
            'user_id' => user()->id,
            'status' => 2,
            'detail' => $this->input('detail'),
        ];
    }

    public function detailData(CashierRefund $refund): array
    {
        return collect($this->input('detail'))->map(function (array $detail) use ($refund): array {
            return [
                'status' => $refund->status,
                'cashier_refund_id' => $refund->id,
                'customer_id' => $refund->customer_id,
                'cashier_id' => null,
                'customer_product_id' => $detail['customer_product_id'] ?? null,
                'customer_goods_id' => $detail['customer_goods_id'] ?? null,
                'package_id' => $detail['package_id'] ?? null,
                'package_name' => $detail['package_name'] ?? null,
                'product_id' => $detail['product_id'] ?? null,
                'product_name' => $detail['product_name'] ?? null,
                'goods_id' => $detail['goods_id'] ?? null,
                'goods_name' => $detail['goods_name'] ?? null,
                'times' => $detail['times'],
                'unit_id' => $detail['unit_id'] ?? null,
                'specs' => $detail['specs'] ?? null,
                'department_id' => $detail['department_id'],
                'amount' => -1 * abs($detail['amount']),
                'salesman' => $detail['salesman'],
                'user_id' => user()->id,
                'cashier_user_id' => null,
                'remark' => $detail['remark'] ?? null,
            ];
        })->all();
    }
}
