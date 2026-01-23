<?php

namespace App\Http\Controllers\Web;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Repositorys\FollowupRepository;
use App\Http\Requests\Web\ReportRequest;
use App\Repositorys\ErpReportRepository;
use App\Repositorys\ReceptionRepository;
use App\Repositorys\CustomerReportRepository;

class ReportController extends Controller
{
    /**
     * 客户消费明细表
     * @param CustomerReportRepository $repository
     * @param Request $request
     * @return JsonResponse
     */
    public function customerProduct(CustomerReportRepository $repository, Request $request): JsonResponse
    {
        return response_success(
            $repository->product($request)
        );
    }

    /**
     *  客户物品明细表
     * @param CustomerReportRepository $report
     * @param Request $request
     * @return JsonResponse
     */
    public function customerGoods(CustomerReportRepository $report, Request $request): JsonResponse
    {
        return response_success(
            $report->goods($request)
        );
    }

    /**
     * 客户退款明细表
     * @param CustomerReportRepository $repository
     * @param ReportRequest $request
     * @return JsonResponse
     */
    public function customerRefund(CustomerReportRepository $repository, ReportRequest $request): JsonResponse
    {
        return response_success(
            $repository->refund($request)
        );
    }

    /**
     * 零售出料明细表
     * @param ErpReportRepository $report
     * @param Request $request
     * @return JsonResponse
     */
    public function retailOutboundDetail(ErpReportRepository $report, Request $request): JsonResponse
    {
        return response_success(
            $report->retailOutboundDetail($request)
        );
    }

    /**
     * 咨询成功率分析表
     * @param ReceptionRepository $report
     * @param Request $request
     * @return JsonResponse
     */
    public function receptionProductAnalysis(ReceptionRepository $report, Request $request): JsonResponse
    {
        return response_success(
            $report->product($request)
        );
    }

    /**
     * 现场咨询成功率分析表之上门明细
     * @param ReceptionRepository $report
     * @param Request $request
     * @return JsonResponse
     */
    public function receptionProductAnalysisDetail(ReceptionRepository $report, Request $request): JsonResponse
    {
        return response_success(
            $report->receptionProductAnalysisDetail($request)
        );
    }

    /**
     * 回访情况统计表
     * @param FollowupRepository $report
     * @param Request $request
     * @return JsonResponse
     */
    public function followupStatistics(FollowupRepository $report, Request $request): JsonResponse
    {
        return response_success(
            $report->statistics($request)
        );
    }
}
