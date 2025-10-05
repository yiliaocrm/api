<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Print\CashierCollectionRequest;
use App\Http\Requests\Print\CashierRequest;
use App\Http\Requests\Print\DepartmentPickingRequest;
use App\Http\Requests\Print\PurchaseDetailRequest;
use App\Http\Requests\Print\PurchaseReturnRequest;
use App\Http\Requests\Web\PrintRequest;
use Illuminate\Http\JsonResponse;

class PrintController extends Controller
{
    /**
     * （收费、退款、还款）打印数据
     * @param CashierRequest $request
     * @return JsonResponse
     */
    public function cashier(CashierRequest $request): JsonResponse
    {
        $cashier  = $request->getCashier();
        $template = $request->getPrintTemplate();

        $cashier->load([
            'pays.account:id,name',
            'customer:id,name,idcard,sex',
            'details.goods.expenseCategory:id,name',
            'details.product.expenseCategory:id,name',
            'details.goods:id,name,short_name,expense_category_id',
            'details.product:id,name,print_name,expense_category_id',
            'operatorUser:id,name',
        ]);

        $cashier->setAttribute('print_at', now()->toDateTimeString());
        $cashier->setAttribute('payment_methods', $request->getPaymentMethods());
        $cashier->setAttribute('expense_categories', $request->getExpenseCategories());

        return response_success([
            'data'     => $cashier,
            'template' => $template
        ]);
    }

    /**
     * 科室领料打印数据
     * @param DepartmentPickingRequest $request
     * @return JsonResponse
     */
    public function departmentPicking(DepartmentPickingRequest $request): JsonResponse
    {
        $picking  = $request->getDepartmentPicking();
        $template = $request->getPrintTemplate();

        $picking->load([
            'details',
            'user:id,name',
            'auditor:id,name',
            'warehouse:id,name',
            'department:id,name',
        ]);

        $picking->setAttribute('print_at', now()->toDateTimeString());

        return response_success([
            'data'     => $picking,
            'template' => $template
        ]);
    }

    /**
     * 库存调拨
     * @param PrintRequest $request
     * @return JsonResponse
     */
    public function inventoryTransfer(PrintRequest $request): JsonResponse
    {
        $transfer = $request->getInventoryTransfer();
        $template = $request->getPrintTemplate('inventory_transfer');

        $transfer->load([
            'details',
            'inWarehouse:id,name',
            'outWarehouse:id,name',
            'user:id,name',
            'checkUser:id,name',
            'createUser:id,name',
        ]);

        $transfer->setAttribute('print_at', now()->toDateTimeString());

        return response_success([
            'data'     => $transfer,
            'template' => $template
        ]);
    }

    /**
     * 进货入库
     * @param PurchaseDetailRequest $request
     * @return JsonResponse
     */
    public function purchaseDetail(PurchaseDetailRequest $request): JsonResponse
    {
        $purchase = $request->getPurchase();
        $template = $request->getPrintTemplate();

        $purchase->load([
            'details',
            'supplier:id,name',
            'warehouse:id,name',
            'user:id,name',
            'auditor:id,name',
            'createUser:id,name',
        ]);

        $purchase->setAttribute('print_at', now()->toDateTimeString());

        return response_success([
            'data'     => $purchase,
            'template' => $template
        ]);
    }

    /**
     * 退货出库
     * @param PurchaseReturnRequest $request
     * @return JsonResponse
     */
    public function purchaseReturn(PurchaseReturnRequest $request): JsonResponse
    {
        $purchaseReturn = $request->getPurchaseReturn();
        $template       = $request->getPrintTemplate();

        $purchaseReturn->load([
            'details',
            'warehouse:id,name',
            'user:id,name',
            'auditor:id,name',
            'createUser:id,name',
        ]);

        $purchaseReturn->setAttribute('print_at', now()->toDateTimeString());

        return response_success([
            'data'     => $purchaseReturn,
            'template' => $template
        ]);
    }

    /**
     * 收费汇总表
     * @param CashierCollectionRequest $request
     * @return JsonResponse
     */
    public function cashierCollection(CashierCollectionRequest $request): JsonResponse
    {
        $data     = $request->getCashierCollection();
        $template = $request->getPrintTemplate();

        return response_success([
            'data'     => $data,
            'template' => $template
        ]);
    }

    /**
     * 开票管理
     * @param PrintRequest $request
     * @return JsonResponse
     */
    public function cashierInvoice(PrintRequest $request): JsonResponse
    {
        $invoice  = $request->getCashierInvoice();
        $template = $request->getPrintTemplate('cashier_invoice');

        $invoice->load([
            'details',
            'customer:id,name,idcard,sex',
            'details.goods.expenseCategory:id,name',
            'details.product.expenseCategory:id,name',
            'details.goods:id,name,short_name,expense_category_id',
            'details.product:id,name,print_name,expense_category_id',
            'createUser:id,name',
        ]);

        $invoice->setAttribute('print_at', now()->toDateTimeString());
        $invoice->setAttribute('expense_categories', $request->getCashierInvoiceExpenseCategories());

        return response_success([
            'data'     => $invoice,
            'template' => $template
        ]);
    }

    /**
     * 报损单
     * @param PrintRequest $request
     * @return JsonResponse
     */
    public function inventoryLoss(PrintRequest $request): JsonResponse
    {
        $loss     = $request->getInventoryLoss();
        $template = $request->getPrintTemplate('inventory_loss');

        $loss->load([
            'details',
            'user:id,name',
            'checkUser:id,name',
            'createUser:id,name',
            'warehouse:id,name',
            'department:id,name',
        ]);

        $loss->setAttribute('print_at', now()->toDateTimeString());

        return response_success([
            'data'     => $loss,
            'template' => $template
        ]);
    }

    /**
     * 报溢单
     * @param PrintRequest $request
     * @return JsonResponse
     */
    public function inventoryOverflow(PrintRequest $request): JsonResponse
    {
        $overflow = $request->getInventoryOverflow();
        $template = $request->getPrintTemplate('inventory_overflow');

        $overflow->load([
            'details',
            'user:id,name',
            'checkUser:id,name',
            'createUser:id,name',
            'warehouse:id,name',
            'department:id,name',
        ]);

        $overflow->setAttribute('print_at', now()->toDateTimeString());

        return response_success([
            'data'     => $overflow,
            'template' => $template
        ]);
    }
}
