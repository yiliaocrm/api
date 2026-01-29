<?php

namespace App\Http\Controllers\Web;

use App\Exports\AppointmentExport;
use App\Exports\CashierDetailExport;
use App\Exports\CashierExport;
use App\Exports\CashierPayExport;
use App\Exports\CashierRefundExport;
use App\Exports\ConsultantDetailExport;
use App\Exports\ConsultantOrderExport;
use App\Exports\ConsumableDetailExport;
use App\Exports\CouponDetailExport;
use App\Exports\CustomerDepositDetailExport;
use App\Exports\CustomerExport;
use App\Exports\CustomerGoodsExport;
use App\Exports\CustomerIntegralExport;
use App\Exports\CustomerLogExport;
use App\Exports\CustomerProductExport;
use App\Exports\DepartmentPickingDetailExport;
use App\Exports\ErkaiDetailExport;
use App\Exports\FollowupStatisticExport;
use App\Exports\GoodsExport;
use App\Exports\InventoryAlarmExport;
use App\Exports\InventoryBatchExport;
use App\Exports\InventoryDetailExport;
use App\Exports\InventoryExpiryExport;
use App\Exports\InventoryExport;
use App\Exports\ProductRankingExport;
use App\Exports\PurchaseDetailExport;
use App\Exports\ReportCashierArrearageDetailExport;
use App\Exports\ReportCashierListExport;
use App\Exports\RetailOutboundDetailExport;
use App\Exports\SalesPerformanceExport;
use App\Exports\TreatmentDetailExport;
use App\Exports\TreatmentRecordExport;
use App\Exports\UserExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\ExportRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ExportController extends Controller
{
    /**
     * 顾客信息导出
     *
     * @throws ValidationException
     */
    public function customer(ExportRequest $request): JsonResponse
    {
        $name = $request->input('fileName', '顾客列表');
        $task = $request->createExportTask($name);
        dispatch(new CustomerExport($request->all(), $task, tenant('id'), user()->id));

        return response_success();
    }

    /**
     * 商品信息导出
     */
    public function goods(Request $request): GoodsExport
    {
        return new GoodsExport;
    }

    /**
     * 库存查询
     */
    public function inventory(ExportRequest $request): InventoryExport
    {
        return new InventoryExport;
    }

    /**
     * 顾客物品明细表
     *
     * @throws ValidationException
     */
    public function customerGoods(ExportRequest $request): JsonResponse
    {
        $name = $request->input('fileName', '顾客物品明细表');
        $task = $request->createExportTask($name);
        dispatch(new CustomerGoodsExport($request->all(), $task, tenant('id'), user()->id));

        return response_success();
    }

    /**
     * 客户项目明细表
     *
     * @throws ValidationException
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
     *
     * @throws ValidationException
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
     *
     * @throws ValidationException
     */
    public function customerDepositDetail(ExportRequest $request): JsonResponse
    {
        $name = $request->input('fileName', '预收账款明细表');
        $task = $request->createExportTask($name);
        dispatch(new CustomerDepositDetailExport($request->all(), $task, tenant('id'), user()->id));

        return response_success();
    }

    /**
     * 职工工作明细表
     *
     * @throws ValidationException
     */
    public function salesPerformance(ExportRequest $request): JsonResponse
    {
        $name = $request->input('fileName', '职工工作明细表');
        $task = $request->createExportTask($name);
        dispatch(new SalesPerformanceExport($request->all(), $task, tenant('id'), user()->id));

        return response_success($task);
    }

    /**
     * 导出[收费列表]
     */
    public function cashierIndex(Request $request): CashierExport
    {
        return new CashierExport($request);
    }

    /**
     * 导出[营收明细]
     *
     * @throws ValidationException
     */
    public function cashierDetail(ExportRequest $request): JsonResponse
    {
        $name = $request->input('fileName', '营收明细');
        $task = $request->createExportTask($name);
        dispatch(new CashierDetailExport($request->all(), $task, tenant('id'), user()->id));

        return response_success();
    }

    /**
     * 导出[账户流水]
     *
     * @throws ValidationException
     */
    public function cashierPay(ExportRequest $request): JsonResponse
    {
        $name = $request->input('fileName', '账户流水');
        $task = $request->createExportTask($name);
        dispatch(new CashierPayExport($request->all(), $task, tenant('id'), user()->id));

        return response_success();
    }

    /**
     * 导出[退款明细]
     *
     * @throws ValidationException
     */
    public function cashierRefund(ExportRequest $request): JsonResponse
    {
        $name = $request->input('fileName', '顾客退款明细表');
        $task = $request->createExportTask($name);
        dispatch(new CashierRefundExport($request->all(), $task, tenant('id'), user()->id));

        return response_success();
    }

    /**
     * 导出[扣划记录]
     *
     * @throws ValidationException
     */
    public function treatmentRecord(ExportRequest $request): JsonResponse
    {
        $name = $request->input('fileName', '治疗记录');
        $task = $request->createExportTask($name);
        dispatch(new TreatmentRecordExport($request->all(), $task, tenant('id'), user()->id));

        return response_success();
    }

    /**
     * 导出[治疗划扣明细表]
     *
     * @throws ValidationException
     */
    public function treatmentDetail(ExportRequest $request): JsonResponse
    {
        $name = $request->input('fileName', '治疗划扣明细表');
        $task = $request->createExportTask($name);
        dispatch(new TreatmentDetailExport($request->all(), $task, tenant('id'), user()->id));

        return response_success();
    }

    /**
     * 导出[收费明细表]
     *
     * @throws ValidationException
     */
    public function cashierList(ExportRequest $request): JsonResponse
    {
        $name = $request->input('fileName', '收费明细表');
        $task = $request->createExportTask($name);
        dispatch(new ReportCashierListExport($request->all(), $task, tenant('id'), user()->id));

        return response_success();
    }

    /**
     * 导出[现场咨询明细表]
     *
     * @throws ValidationException
     */
    public function consultantDetail(ExportRequest $request): JsonResponse
    {
        $name = $request->input('fileName', '现场咨询明细表');
        $task = $request->createExportTask($name);
        dispatch(new ConsultantDetailExport($request->all(), $task, tenant('id'), user()->id));

        return response_success();
    }

    /**
     * 导出[现场开单明细表]
     *
     * @throws ValidationException
     */
    public function consultantOrder(ExportRequest $request): JsonResponse
    {
        $name = $request->input('fileName', '现场开单明细表');
        $task = $request->createExportTask($name);
        dispatch(new ConsultantOrderExport($request->all(), $task, tenant('id'), user()->id));

        return response_success();
    }

    /**
     * 导出[项目销售排行榜]
     *
     * @throws ValidationException
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
     *
     * @throws ValidationException
     */
    public function purchaseDetail(ExportRequest $request): JsonResponse
    {
        $name = $request->input('fileName', '进货入库明细表');
        $task = $request->createExportTask($name);
        dispatch(new PurchaseDetailExport($request->all(), $task, tenant('id'), user()->id));

        return response_success();
    }

    /**
     * 导出[零售出料明细表]
     *
     * @throws ValidationException
     */
    public function retailOutboundDetail(ExportRequest $request): JsonResponse
    {
        $name = $request->input('fileName', '零售出料明细表');
        $task = $request->createExportTask($name);
        dispatch(new RetailOutboundDetailExport($request->all(), $task, tenant('id'), user()->id));

        return response_success();
    }

    /**
     * 导出[库存变动明细表]
     *
     * @throws ValidationException
     */
    public function inventoryDetail(ExportRequest $request): JsonResponse
    {
        $name = $request->input('fileName', '库存变动明细表');
        $task = $request->createExportTask($name);
        dispatch(new InventoryDetailExport($request->all(), $task, tenant('id'), user()->id));

        return response_success();
    }

    /**
     * 导出[库存批次明细表]
     */
    public function inventoryBatch(ExportRequest $request): InventoryBatchExport
    {
        return new InventoryBatchExport($request);
    }

    /**
     * 导出[库存预警]
     *
     * @throws ValidationException
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
     *
     * @throws ValidationException
     */
    public function inventoryExpiry(ExportRequest $request): JsonResponse
    {
        $name = $request->input('fileName', '过期预警');
        $task = $request->createExportTask($name);
        dispatch(new InventoryExpiryExport($request->all(), $task, tenant('id'), user()->id));

        return response_success();
    }

    /**
     * 导出[回访情况统计表]
     */
    public function followupStatistic(Request $request): FollowupStatisticExport
    {
        return new FollowupStatisticExport($request);
    }

    /**
     * 导出[用料登记明细表]
     *
     * @throws ValidationException
     */
    public function consumableDetail(ExportRequest $request): JsonResponse
    {
        $name = $request->input('fileName', '用料登记明细表');
        $task = $request->createExportTask($name);
        dispatch(new ConsumableDetailExport($request->all(), $task, tenant('id'), user()->id));

        return response_success();
    }

    /**
     * 导出[科室领料明细表]
     *
     * @throws ValidationException
     */
    public function departmentPickingDetail(ExportRequest $request): JsonResponse
    {
        $name = $request->input('fileName', '室领料明细表');
        $task = $request->createExportTask($name);
        dispatch(new DepartmentPickingDetailExport($request->all(), $task, tenant('id'), user()->id));

        return response_success();
    }

    /**
     * 导出[二开零购明细表]
     *
     * @throws ValidationException
     */
    public function erkaiDetail(ExportRequest $request): JsonResponse
    {
        $name = $request->input('fileName', '二开零购明细表');
        $task = $request->createExportTask($name);
        dispatch(new ErkaiDetailExport($request->all(), $task, tenant('id'), user()->id));

        return response_success();
    }

    /**
     * 导出[领券记录]
     */
    public function couponDetail(): CouponDetailExport
    {
        return new CouponDetailExport;
    }

    /**
     * 导出顾客积分变动明细表
     *
     * @throws ValidationException
     */
    public function customerIntegral(ExportRequest $request): JsonResponse
    {
        $name = $request->input('fileName', '顾客积分明细表');
        $task = $request->createExportTask($name);
        dispatch(new CustomerIntegralExport($request->all(), $task, tenant('id'), user()->id));

        return response_success();
    }

    /**
     * 员工信息导出
     *
     * @throws ValidationException
     */
    public function user(ExportRequest $request): JsonResponse
    {
        $name = $request->input('fileName', '员工列表');
        $task = $request->createExportTask($name);
        dispatch(new UserExport($request->all(), $task, tenant('id'), user()->id));

        return response_success();
    }

    /**
     * 预约记录导出
     *
     * @throws ValidationException
     */
    public function appointment(ExportRequest $request): JsonResponse
    {
        $name = $request->input('fileName', '预约记录表');
        $task = $request->createExportTask($name);
        dispatch(new AppointmentExport($request->all(), $task, tenant('id'), user()->id));

        return response_success();
    }

    /**
     * 导出[应收账款明细表]
     *
     * @throws ValidationException
     */
    public function arrearageDetail(ExportRequest $request): JsonResponse
    {
        $name = $request->input('fileName', '应收账款明细表');
        $task = $request->createExportTask($name);
        dispatch(new ReportCashierArrearageDetailExport($request->all(), $task, tenant('id'), user()->id));

        return response_success();
    }
}
