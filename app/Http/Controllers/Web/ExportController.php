<?php

namespace App\Http\Controllers\Web;

use App\Exports\UserExport;
use App\Exports\GoodsExport;
use App\Exports\CashierExport;
use App\Exports\CustomerExport;
use App\Exports\InventoryExport;
use App\Exports\CashierPayExport;
use App\Exports\CustomerLogExport;
use App\Exports\ErkaiDetailExport;
use App\Exports\CouponDetailExport;
use App\Exports\CashierDetailExport;
use App\Exports\CashierRefundExport;
use App\Exports\CustomerGoodsExport;
use App\Exports\CustomerIntegralExport;
use App\Exports\ProductRankingExport;
use App\Exports\PurchaseDetailExport;
use App\Exports\InventoryAlarmExport;
use App\Exports\InventoryBatchExport;
use App\Exports\TreatmentRecordExport;
use App\Exports\InventoryDetailExport;
use App\Exports\InventoryExpiryExport;
use App\Exports\CustomerProductExport;
use App\Exports\ConsultantOrderExport;
use App\Exports\SalesPerformanceExport;
use App\Exports\ConsultantDetailExport;
use App\Exports\ConsumableDetailExport;
use App\Exports\FollowupStatisticExport;
use App\Exports\ReportCashierListExport;
use App\Exports\CustomerDepositDetailExport;
use App\Exports\DepartmentPickingDetailExport;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\ExportRequest;

class ExportController extends Controller
{
    /**
     * 顾客信息导出
     * @param ExportRequest $request
     * @return JsonResponse
     */
    public function customer(ExportRequest $request): JsonResponse
    {
        $name = $request->input('fileName', '顾客列表');
        $task = $request->createExportTask($name);
        dispatch(new CustomerExport($request->all(), $task, user()->id));
        return response_success();
    }

    /**
     * 商品信息导出
     * @param Request $request
     * @return GoodsExport
     */
    public function goods(Request $request): GoodsExport
    {
        return new GoodsExport();
    }

    /**
     * 库存查询
     * @param ExportRequest $request
     * @return InventoryExport
     */
    public function inventory(ExportRequest $request): InventoryExport
    {
        return new InventoryExport();
    }

    /**
     * 顾客物品明细表
     * @param ExportRequest $request
     * @return JsonResponse
     */
    public function customerGoods(ExportRequest $request): JsonResponse
    {
        $name = $request->input('fileName', '顾客物品明细表');
        $task = $request->createExportTask($name);
        dispatch(new CustomerGoodsExport($request->all(), $task));
        return response_success();
    }

    /**
     * 客户项目明细表
     * @param ExportRequest $request
     * @return JsonResponse
     */
    public function customerProduct(ExportRequest $request): JsonResponse
    {
        $name = $request->input('fileName', '客户项目明细表');
        $task = $request->createExportTask($name);
        dispatch(new CustomerProductExport($request->all(), $task, tenant('id'), user()->id));
        return response_success();
    }

    /**
     * 顾客日志
     * @param ExportRequest $request
     * @return JsonResponse
     */
    public function customerLog(ExportRequest $request): JsonResponse
    {
        $name = $request->input('fileName', '顾客日志');
        $task = $request->createExportTask($name);
        dispatch(new CustomerLogExport($request->all(), $task, tenant('id'), user()->id));
        return response_success();
    }

    /**
     * 预收账款变动明细表
     * @param Request $request
     * @return CustomerDepositDetailExport
     */
    public function customerDepositDetail(Request $request): CustomerDepositDetailExport
    {
        return new CustomerDepositDetailExport($request);
    }

    /**
     * 职工工作明细表
     * @param ExportRequest $request
     * @return JsonResponse
     */
    public function salesPerformance(ExportRequest $request): JsonResponse
    {
        $name = $request->input('fileName', '职工工作明细表');
        $task = $request->createExportTask($name);
        dispatch(new SalesPerformanceExport($request->all(), $task, user()->id));
        return response_success($task);
    }

    /**
     * 导出[收费列表]
     * @param Request $request
     * @return CashierExport
     */
    public function cashierIndex(Request $request): CashierExport
    {
        return new CashierExport($request);
    }

    /**
     * 导出[营收明细]
     * @param Request $request
     * @return CashierDetailExport
     */
    public function cashierDetail(Request $request): CashierDetailExport
    {
        return new CashierDetailExport($request);
    }

    /**
     * 导出[账户流水]
     * @param Request $request
     * @return CashierPayExport
     */
    public function cashierPay(Request $request): CashierPayExport
    {
        return new CashierPayExport($request);
    }

    /**
     * 导出[退款明细]
     * @param ExportRequest $request
     * @return JsonResponse
     */
    public function cashierRefund(ExportRequest $request): JsonResponse
    {
        $name = $request->input('fileName', '顾客退款明细表');
        $task = $request->createExportTask($name);
        dispatch(new CashierRefundExport($request->all(), $task));
        return response_success();
    }

    /**
     * 导出[扣划记录]
     * @param Request $request
     * @return TreatmentRecordExport
     */
    public function treatmentRecord(Request $request): TreatmentRecordExport
    {
        return new TreatmentRecordExport($request);
    }

    /**
     * 导出[收费明细表]
     * @param Request $request
     * @return ReportCashierListExport
     */
    public function cashierList(Request $request): ReportCashierListExport
    {
        return new ReportCashierListExport($request);
    }

    /**
     * 导出[现场咨询明细表]
     * @param Request $request
     * @return ConsultantDetailExport
     */
    public function consultantDetail(Request $request): ConsultantDetailExport
    {
        return new ConsultantDetailExport($request);
    }

    /**
     * 导出[现场开单明细表]
     * @param Request $request
     * @return ConsultantOrderExport
     */
    public function consultantOrder(Request $request): ConsultantOrderExport
    {
        return new ConsultantOrderExport($request);
    }

    /**
     * 导出[项目销售排行榜]
     * @param ExportRequest $request
     * @return JsonResponse
     */
    public function productRanking(ExportRequest $request): JsonResponse
    {
        $name = $request->input('fileName', '项目销售排行榜');
        $task = $request->createExportTask($name);
        dispatch(new ProductRankingExport($request->all(), $task, tenant('id'), user()->id));
        return response_success();
    }

    /**
     * 导出[进货入库明细表]
     * @param ExportRequest $request
     * @return JsonResponse
     */
    public function purchaseDetail(ExportRequest $request): JsonResponse
    {
        $name = $request->input('fileName', '进货入库明细表');
        $task = $request->createExportTask($name);
        dispatch(new PurchaseDetailExport($request->all(), $task, user()->id));
        return response_success();
    }

    /**
     * 导出[库存变动明细表]
     * @param Request $request
     * @return InventoryDetailExport
     */
    public function inventoryDetail(Request $request): InventoryDetailExport
    {
        return new InventoryDetailExport($request);
    }

    /**
     * 导出[库存批次明细表]
     * @param ExportRequest $request
     * @return InventoryBatchExport
     */
    public function inventoryBatch(ExportRequest $request): InventoryBatchExport
    {
        return new InventoryBatchExport($request);
    }

    /**
     * 导出[库存预警]
     * @param ExportRequest $request
     * @return JsonResponse
     */
    public function inventoryAlarm(ExportRequest $request): JsonResponse
    {
        $name = $request->input('fileName', '库存预警');
        $task = $request->createExportTask($name);
        dispatch(new InventoryAlarmExport($request->all(), $task, tenant('id'), user()->id));
        return response_success();
    }

    /**
     * 导出[过期预警]
     * @param Request $request
     * @return InventoryExpiryExport
     */
    public function inventoryExpiry(Request $request): InventoryExpiryExport
    {
        return new InventoryExpiryExport($request);
    }

    /**
     * 导出[回访情况统计表]
     * @param Request $request
     * @return FollowupStatisticExport
     */
    public function followupStatistic(Request $request): FollowupStatisticExport
    {
        return new FollowupStatisticExport($request);
    }

    /**
     * 导出[用料登记明细表]
     * @param ExportRequest $request
     * @return JsonResponse
     */
    public function consumableDetail(ExportRequest $request): JsonResponse
    {
        $name = $request->input('fileName', '用料登记明细表');
        $task = $request->createExportTask($name);
        dispatch(new ConsumableDetailExport($request->all(), $task, user()->id));
        return response_success();
    }

    /**
     * 导出[科室领料明细表]
     * @param ExportRequest $request
     * @return JsonResponse
     */
    public function departmentPickingDetail(ExportRequest $request): JsonResponse
    {
        $name = $request->input('fileName', '科室领料明细表');
        $task = $request->createExportTask($name);
        dispatch(new DepartmentPickingDetailExport($request->all(), $task, user()->id));
        return response_success();
    }

    /**
     * 导出[二开零购明细表]
     * @param Request $request
     * @return ErkaiDetailExport
     */
    public function erkaiDetail(Request $request): ErkaiDetailExport
    {
        return new ErkaiDetailExport($request);
    }

    /**
     * 导出[领券记录]
     * @return CouponDetailExport
     */
    public function couponDetail(): CouponDetailExport
    {
        return new CouponDetailExport();
    }

    /**
     * 导出顾客积分变动明细表
     * @param ExportRequest $request
     * @return JsonResponse
     */
    public function customerIntegral(ExportRequest $request): JsonResponse
    {
        $name = $request->input('fileName', '顾客积分明细表');
        $task = $request->createExportTask($name);
        dispatch(new CustomerIntegralExport($request->all(), $task));
        return response_success();
    }

    /**
     * 员工信息导出
     * @param ExportRequest $request
     * @return JsonResponse
     */
    public function user(ExportRequest $request): JsonResponse
    {
        $name = $request->input('fileName', '员工列表');
        $task = $request->createExportTask($name);
        dispatch(new UserExport($request->all(), $task, user()->id));
        return response_success();
    }
}
