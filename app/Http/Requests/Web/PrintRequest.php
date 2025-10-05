<?php

namespace App\Http\Requests\Web;

use App\Models\InventoryLoss;
use App\Models\PrintTemplate;
use App\Models\CashierInvoice;
use App\Models\InventoryTransfer;
use Illuminate\Foundation\Http\FormRequest;

class PrintRequest extends FormRequest
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
            'inventoryLoss' => $this->getInventoryLossRules(),
            'cashierInvoice' => $this->getCashierInvoiceRules(),
            'inventoryTransfer' => $this->getInventoryTransferRules(),
            'inventoryOverflow' => $this->getInventoryOverflowRules(),
        };
    }

    public function messages(): array
    {
        return match (request()->route()->getActionMethod()) {
            default => [],
            'inventoryLoss' => $this->getInventoryLossMessages(),
            'cashierInvoice' => $this->getCashierInvoiceMessages(),
            'inventoryTransfer' => $this->getInventoryTransferMessages(),
            'inventoryOverflow' => $this->getInventoryOverflowMessages(),
        };
    }

    private function getCashierInvoiceRules(): array
    {
        return [
            'id' => [
                'required',
                'exists:cashier_invoices,id',
                function ($attribute, $value, $fail) {
                    if (!$this->getPrintTemplate('cashier_invoice')) {
                        $fail('[开票管理-发票]默认打印模板不存在');
                    }
                }
            ],
        ];
    }

    private function getCashierInvoiceMessages(): array
    {
        return [
            'id.required' => '发票记录ID不能为空',
            'id.exists'   => '发票记录不存在',
        ];
    }

    private function getInventoryTransferRules(): array
    {
        return [
            'id' => [
                'required',
                'exists:inventory_transfer,id',
                function ($attribute, $value, $fail) {
                    if (!$this->getPrintTemplate('inventory_transfer')) {
                        $fail('[库存调拨]默认打印模板不存在');
                    }
                }
            ]
        ];
    }

    private function getInventoryTransferMessages(): array
    {
        return [
            'id.required' => '库存调拨记录ID不能为空',
            'id.exists'   => '库存调拨记录不存在',
        ];
    }

    /**
     * 获取发票记录
     * @return CashierInvoice|null
     */
    public function getCashierInvoice(): ?CashierInvoice
    {
        return CashierInvoice::query()->find(
            $this->input('id')
        );
    }

    /**
     * 获取库存调拨记录
     * @return InventoryTransfer|null
     */
    public function getInventoryTransfer(): ?InventoryTransfer
    {
        return InventoryTransfer::query()->find(
            $this->input('id')
        );
    }

    /**
     * 获取报损单记录
     * @return InventoryLoss|null
     */
    public function getInventoryLoss(): ?InventoryLoss
    {
        return InventoryLoss::query()->find(
            $this->input('id')
        );
    }

    /**
     * 获取报损单记录
     * @return InventoryLoss|null
     */
    public function getInventoryOverflow(): ?InventoryLoss
    {
        return InventoryLoss::query()->find(
            $this->input('id')
        );
    }

    /**
     * 获取打印模板
     * @param string $type
     * @return PrintTemplate|null
     */
    public function getPrintTemplate(string $type): ?PrintTemplate
    {
        return PrintTemplate::query()
            ->where('type', $type)
            ->where('default', 1)
            ->first();
    }

    /**
     * 获取开票项目类别
     * @return mixed
     */
    public function getCashierInvoiceExpenseCategories(): mixed
    {
        return $this->getCashierInvoice()->details->mapToGroups(function ($detail) {
            if ($detail->product_id) {
                return [$detail->product->expenseCategory->name => $detail->invoice_amount];
            }
            return [$detail->goods->expenseCategory->name => $detail->invoice_amount];
        })->map(function ($group) {
            return $group->sum();
        });
    }

    private function getInventoryLossRules(): array
    {
        return [
            'id' => [
                'required',
                'exists:inventory_losses,id',
                function ($attribute, $value, $fail) {
                    if (!$this->getPrintTemplate('inventory_loss')) {
                        $fail('[报损单]默认打印模板不存在');
                    }
                }
            ]
        ];
    }

    private function getInventoryLossMessages(): array
    {
        return [
            'id.required' => '报损单记录ID不能为空',
            'id.exists'   => '报损单记录不存在',
        ];
    }

    private function getInventoryOverflowRules(): array
    {
        return [
            'id' => [
                'required',
                'exists:inventory_overflows,id',
                function ($attribute, $value, $fail) {
                    if (!$this->getPrintTemplate('inventory_overflow')) {
                        $fail('[报溢单]默认打印模板不存在');
                    }
                }
            ]
        ];
    }

    private function getInventoryOverflowMessages(): array
    {
        return [
            'id.required' => '报溢单记录ID不能为空',
            'id.exists'   => '报溢单记录不存在',
        ];
    }
}
