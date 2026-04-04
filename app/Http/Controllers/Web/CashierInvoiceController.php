<?php

namespace App\Http\Controllers\Web;

use App\Exceptions\HisException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\CashierInvoiceRequest;
use App\Models\CashierInvoice;
use App\Models\CustomerGoods;
use App\Models\CustomerProduct;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Throwable;

class CashierInvoiceController extends Controller
{
    /**
     * 开票列表
     *
     * @param  Request  $request
     */
    public function index(CashierInvoiceRequest $request): JsonResponse
    {
        $rows = $request->input('rows', 10);
        $sort = $request->input('sort', 'cashier_invoices.created_at');
        $order = $request->input('order', 'desc');
        $keyword = $request->input('keyword');

        $query = CashierInvoice::query()
            ->with([
                'customer:id,name,idcard',
                'details',
                'createUser:id,name',
            ])
            ->select('cashier_invoices.*')
            ->leftJoin('customer', 'customer.id', '=', 'cashier_invoices.customer_id')
            ->when($keyword, fn (Builder $query) => $query->where('customer.keyword', 'like', "%{$keyword}%"))
            ->queryConditions('CashierInvoiceIndex')
            ->orderBy($sort, $order)
            ->paginate($rows);

        return response_success([
            'rows' => $query->items(),
            'total' => $query->total(),
        ]);
    }

    /**
     * 已购项目记录
     */
    public function customerProduct(CashierInvoiceRequest $request): JsonResponse
    {
        $data = CustomerProduct::query()
            ->where('customer_id', $request->input('customer_id'))
            ->get();

        return response_success($data);
    }

    /**
     * 已购物品记录
     */
    public function customerGoods(CashierInvoiceRequest $request): JsonResponse
    {
        $data = CustomerGoods::query()
            ->where('customer_id', $request->input('customer_id'))
            ->get();

        return response_success($data);
    }

    /**
     * 新增开票记录
     *
     * @throws HisException|Throwable
     */
    public function create(CashierInvoiceRequest $request): JsonResponse
    {
        DB::beginTransaction();
        try {
            $invoice = CashierInvoice::query()->create(
                $request->getInvoiceCreateData()
            );
            $invoice->details()->createMany(
                $request->getInvoiceDetailData($invoice->id, $invoice->customer_id)
            );
            DB::commit();

            return response_success($invoice);
        } catch (Exception $e) {
            DB::rollBack();
            throw new HisException($e->getMessage());
        }
    }

    /**
     * 加载开票信息
     */
    public function info(CashierInvoiceRequest $request): JsonResponse
    {
        $invoice = CashierInvoice::query()->find(
            $request->input('id')
        );
        $invoice->load([
            'customer:id,name,idcard',
            'details',
        ]);

        return response_success($invoice);
    }

    /**
     * 更新开票信息
     *
     * @throws HisException|Throwable
     */
    public function update(CashierInvoiceRequest $request): JsonResponse
    {
        DB::beginTransaction();
        try {
            $invoice = CashierInvoice::query()->find(
                $request->input('id')
            );
            $invoice->update(
                $request->getInvoiceCreateData()
            );
            $invoice->details()->delete();
            $invoice->details()->createMany(
                $request->getInvoiceDetailData($invoice->id, $invoice->customer_id)
            );
            DB::commit();

            return response_success($invoice);
        } catch (Exception $e) {
            DB::rollBack();
            throw new HisException($e->getMessage());
        }
    }
}
