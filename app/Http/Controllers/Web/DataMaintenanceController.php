<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\DataMaintenance\CreateCustomerProductRequest;
use App\Http\Requests\DataMaintenance\RemoveCustomerProductRequest;
use App\Services\CustomerProductService;
use App\Services\CustomerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DataMaintenanceController extends Controller
{
    protected CustomerService $customerService;
    protected CustomerProductService $customerProductService;

    public function __construct(
        CustomerService        $customerService,
        CustomerProductService $customerProductService
    )
    {
        $this->customerService        = $customerService;
        $this->customerProductService = $customerProductService;
    }

    /**
     * 查询顾客档案
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        return response_success(
            $this->customerService->getCustomerLists($request)
        );
    }

    /**
     * 接待记录
     * @param Request $request
     * @return JsonResponse
     */
    public function receptions(Request $request): JsonResponse
    {
        return response_success(
            $this->customerService->getCustomerReception($request)
        );
    }

    /**
     * 已购项目
     * @param Request $request
     * @return JsonResponse
     */
    public function customerProduct(Request $request): JsonResponse
    {
        return response_success(
            $this->customerService->getCustomerProduct($request)
        );
    }

    /**
     * 项目补卡
     * @param CreateCustomerProductRequest $request
     * @return JsonResponse
     */
    public function createCustomerProduct(CreateCustomerProductRequest $request): JsonResponse
    {
        $data = [];
        $details = $request->formData();
        foreach ($details as $detail) {
            $data[] =  $this->customerProductService->create(
                $detail
            );
        }
        return response_success($data);
    }

    /**
     * 删除项目
     * @param RemoveCustomerProductRequest $request
     * @return JsonResponse
     */
    public function removeCustomerProduct(RemoveCustomerProductRequest $request): JsonResponse
    {
        $this->customerProductService->remove(
            $request->input('id')
        );
        return response_success();
    }
}
