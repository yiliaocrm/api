<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\ReportRequest;
use App\Repositorys\CustomerReportRepository;
use App\Repositorys\ReceptionRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    /**
     * 客户消费明细表
     */
    public function customerProduct(CustomerReportRepository $repository, Request $request): JsonResponse
    {
        return response_success(
            $repository->product($request)
        );
    }

    /**
     *  客户物品明细表
     */
    public function customerGoods(CustomerReportRepository $report, Request $request): JsonResponse
    {
        return response_success(
            $report->goods($request)
        );
    }

    /**
     * 客户退款明细表
     */
    public function customerRefund(CustomerReportRepository $repository, ReportRequest $request): JsonResponse
    {
        return response_success(
            $repository->refund($request)
        );
    }

    /**
     * 咨询成功率分析表
     */
    public function receptionProductAnalysis(ReceptionRepository $report, Request $request): JsonResponse
    {
        return response_success(
            $report->product($request)
        );
    }

    /**
     * 现场咨询成功率分析表之上门明细
     */
    public function receptionProductAnalysisDetail(ReceptionRepository $report, Request $request): JsonResponse
    {
        return response_success(
            $report->receptionProductAnalysisDetail($request)
        );
    }
}
