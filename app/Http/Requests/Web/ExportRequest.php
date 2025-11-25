<?php

namespace App\Http\Requests\Web;

use App\Models\ExportTask;
use App\Rules\Web\SceneRule;
use Illuminate\Support\Str;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class ExportRequest extends FormRequest
{

    /**
     * [场景化搜索]方法与页面的映射关系
     * @var array
     */
    private array $pages = [
        'cashierRefund' => 'ReportCustomerRefund',
    ];

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
            'customer' => $this->getCustomerRules(),
            'inventory' => $this->getInventoryRules(),
            'cashierRefund' => $this->getExportRules(),
            'cashierPay' => $this->getCashierPayRules(),
            'customerGoods' => $this->getCustomerGoodsRules(),
            'inventoryBatch' => $this->getInventoryBatchRules(),
            'inventoryAlarm' => $this->getInventoryAlarmRules(),
            'inventoryExpiry' => $this->getInventoryExpiryRules(),
            'customerProduct' => $this->getCustomerProductRules(),
            'customerDepositDetail' => $this->getCustomerDepositDetailRules(),
            'salesPerformance' => $this->getSalesPerformanceRules(),
            'purchaseDetail' => $this->getPurchaseDetailRules(),
            'departmentPickingDetail' => $this->getDepartmentPickingDetailRules(),
            'consumableDetail' => $this->getConsumableDetailRules(),
            'productRanking' => $this->getProductRankingRules(),
            'user' => $this->getUserRules(),
            'appointment' => $this->getAppointmentRules(),
            default => []
        };
    }

    public function messages(): array
    {
        return match (request()->route()->getActionMethod()) {
            'customer' => $this->getCustomerMessages(),
            'inventory' => $this->getInventoryMessages(),
            'cashierRefund' => $this->getExportMessages(),
            'cashierPay' => $this->getCashierPayMessages(),
            'customerGoods' => $this->getCustomerGoodsMessages(),
            'inventoryBatch' => $this->getInventoryBatchMessages(),
            'inventoryAlarm' => $this->getInventoryAlarmMessages(),
            'inventoryExpiry' => $this->getInventoryExpiryMessages(),
            'customerProduct' => $this->getCustomerProductMessages(),
            'customerDepositDetail' => $this->getCustomerDepositDetailMessages(),
            'salesPerformance' => $this->getSalesPerformanceMessages(),
            'purchaseDetail' => $this->getPurchaseDetailMessages(),
            'departmentPickingDetail' => $this->getDepartmentPickingDetailMessages(),
            'consumableDetail' => $this->getConsumableDetailMessages(),
            'productRanking' => $this->getProductRankingMessages(),
            'user' => $this->getUserMessages(),
            'appointment' => $this->getAppointmentMessages(),
            default => []
        };
    }

    private function getExportRules(): array
    {
        $rules = [
            'filters' => ['nullable', 'array']
        ];

        $method = request()->route()->getActionMethod();

        if (isset($this->pages[$method])) {
            $rules['filters'][] = new SceneRule($this->pages[$method]);
        }

        return $rules;
    }

    private function getExportMessages(): array
    {
        return [
            'filters.array' => '筛选条件必须是数组',
        ];
    }

    private function getInventoryRules(): array
    {
        return [
            'type_id' => 'required|integer|exists:goods_type,id',
            'keyword' => 'nullable|string',
            'filters' => [
                'nullable',
                'array',
                new SceneRule('InventoryIndex')
            ]
        ];
    }

    private function getInventoryMessages(): array
    {
        return [
            'type_id.required' => '商品分类必须选择',
            'type_id.integer'  => '商品分类格式错误',
            'type_id.exists'   => '商品分类不存在',
            'keyword.string'   => '关键字格式错误',
            'filters.array'    => '筛选条件必须是数组',
        ];
    }

    private function getInventoryBatchRules(): array
    {
        return [
            'filters' => [
                'nullable',
                'array',
                new SceneRule('InventoryBatchsIndex')
            ]
        ];
    }

    private function getInventoryBatchMessages(): array
    {
        return [
            'filters.array' => '筛选条件必须是数组',
        ];
    }

    private function getInventoryAlarmRules(): array
    {
        return [
            'warehouse_id' => 'nullable|integer|exists:warehouse,id',
            'type_id'      => 'nullable|integer|exists:goods_type,id',
            'name'         => 'nullable|string|max:200',
            'status'       => 'nullable|string|in:normal,high,low',
            'filterable'   => 'nullable|string|in:show,hide',
            'fileName'     => 'nullable|string|max:200',
        ];
    }

    private function getInventoryAlarmMessages(): array
    {
        return [
            'warehouse_id.integer' => '[仓库]格式错误',
            'warehouse_id.exists'  => '[仓库]不存在',
            'type_id.integer'      => '[物品分类]格式错误',
            'type_id.exists'       => '[物品分类]不存在',
            'name.string'          => '[物品名称]格式错误',
            'name.max'             => '[物品名称]不能超过200个字符',
            'status.string'        => '[预警状态]格式错误',
            'status.in'            => '[预警状态]值无效',
            'filterable.string'    => '[过滤库存]格式错误',
            'filterable.in'        => '[过滤库存]值无效',
            'fileName.string'      => '文件名称格式错误',
            'fileName.max'         => '文件名称不能超过200个字符',
        ];
    }

    private function getInventoryExpiryRules(): array
    {
        return [
            'warehouse_id' => 'nullable|integer|exists:warehouse,id',
            'type_id'      => 'nullable|integer|exists:goods_type,id',
            'name'         => 'nullable|string|max:200',
            'status'       => 'nullable|string|in:normal,expiring,expired',
            'expiry_diff'  => 'nullable|integer|min:0',
            'fileName'     => 'nullable|string|max:200',
        ];
    }

    private function getInventoryExpiryMessages(): array
    {
        return [
            'warehouse_id.integer' => '[仓库]格式错误',
            'warehouse_id.exists'  => '[仓库]不存在',
            'type_id.integer'      => '[物品分类]格式错误',
            'type_id.exists'       => '[物品分类]不存在',
            'name.string'          => '[物品名称]格式错误',
            'name.max'             => '[物品名称]不能超过200个字符',
            'status.string'        => '[预警状态]格式错误',
            'status.in'            => '[预警状态]值无效',
            'expiry_diff.integer'  => '[剩余天数]格式错误',
            'expiry_diff.min'      => '[剩余天数]不能小于0',
            'fileName.string'      => '文件名称格式错误',
            'fileName.max'         => '文件名称不能超过200个字符',
        ];
    }

    private function getSalesPerformanceRules(): array
    {
        return [
            'filters'    => [
                'nullable',
                'array',
                new SceneRule('ReportPerformanceSales')
            ],
            'created_at' => 'required|array|size:2',
            'keyword'    => 'nullable|string',
            'fileName'   => 'nullable|string|max:200',
        ];
    }

    private function getSalesPerformanceMessages(): array
    {
        return [
            'created_at.required' => '请选择导出时间',
            'created_at.array'    => '导出时间格式错误',
            'created_at.size'     => '导出时间格式错误',
            'keyword.string'      => '关键字格式错误',
            'fileName.string'     => '文件名称格式错误',
            'fileName.max'        => '文件名称不能超过200个字符',
        ];
    }

    private function getCustomerRules(): array
    {
        return [
            'filters' => [
                'nullable',
                'array',
                new SceneRule('CustomerIndex')
            ],
            'keyword' => 'nullable|string|max:200',
        ];
    }

    private function getCustomerMessages(): array
    {
        return [
            'filters.array'  => '筛选条件必须是数组',
            'keyword.string' => '关键字格式错误',
            'keyword.max'    => '关键字不能超过200个字符',
        ];
    }

    private function getCashierPayRules(): array
    {
        return [
            'filters'  => [
                'nullable',
                'array',
                new SceneRule('CashierPayIndex')
            ],
            'date'     => 'required|array|size:2',
            'date.*'   => 'required|date',
            'sort'     => 'nullable|string',
            'order'    => 'nullable|string|in:asc,desc',
            'fileName' => 'nullable|string|max:200',
        ];
    }

    private function getCashierPayMessages(): array
    {
        return [
            'filters.array'   => '筛选条件必须是数组',
            'date.required'   => '[查询日期]不能为空',
            'date.array'      => '[查询日期]格式错误',
            'date.size'       => '[查询日期]必须包含开始和结束日期',
            'date.*.required' => '[查询日期]不能为空',
            'date.*.date'     => '[查询日期]格式错误',
            'sort.string'     => '[排序字段]格式错误',
            'order.string'    => '[排序方向]格式错误',
            'order.in'        => '[排序方向]值无效',
            'fileName.string' => '文件名称格式错误',
            'fileName.max'    => '文件名称不能超过200个字符',
        ];
    }

    /**
     * 生成导出任务
     * @param string $name 任务名称
     * @return ExportTask
     */
    public function createExportTask(string $name): ExportTask
    {
        $params = $this->getExportParameter();
        $hash   = md5(json_encode(array_merge($params, ['user_id' => user()->id])));

        // 检查是否存在进行中的相同导出任务
        $existingTask = ExportTask::query()
            ->where('user_id', user()->id)
            ->where('hash', $hash)
            ->whereIn('status', ['pending', 'processing'])
            ->first();

        if ($existingTask) {
            throw ValidationException::withMessages([
                'export' => '任务进行中，请勿重复操作',
            ]);
        }

        // 导出文件路径
        $path = 'exports/' . date('YmdHis') . '_' . Str::random(6) . '.xlsx';

        return ExportTask::query()->create([
            'name'      => $name,
            'hash'      => $hash,
            'status'    => 'pending',
            'params'    => $params,
            'file_path' => $path,
            'user_id'   => user()->id,
        ]);
    }

    private function getCustomerProductRules(): array
    {
        return [
            'filters'  => [
                'nullable',
                'array',
                new SceneRule('ReportCustomerProduct')
            ],
            'keyword'  => 'nullable|string|max:200',
            'fileName' => 'nullable|string|max:200',
        ];
    }

    private function getCustomerProductMessages(): array
    {
        return [
            'filters.array'   => '筛选条件必须是数组',
            'keyword.string'  => '关键字格式错误',
            'keyword.max'     => '关键字不能超过200个字符',
            'fileName.string' => '文件名称格式错误',
            'fileName.max'    => '文件名称不能超过200个字符',
        ];
    }

    private function getCustomerGoodsRules(): array
    {
        return [
            'filters'  => [
                'nullable',
                'array',
                new SceneRule('ReportCustomerGoods')
            ],
            'keyword'  => 'nullable|string|max:200',
            'fileName' => 'nullable|string|max:200',
        ];
    }

    private function getCustomerGoodsMessages(): array
    {
        return [
            'filters.array'   => '筛选条件必须是数组',
            'keyword.string'  => '关键字格式错误',
            'keyword.max'     => '关键字不能超过200个字符',
            'fileName.string' => '文件名称格式错误',
            'fileName.max'    => '文件名称不能超过200个字符',
        ];
    }

    private function getPurchaseDetailRules(): array
    {
        return [
            'filters'  => [
                'nullable',
                'array',
                new SceneRule('ReportPurchaseDetail')
            ],
            'keyword'  => 'nullable|string|max:200',
            'fileName' => 'nullable|string|max:200',
        ];
    }

    private function getPurchaseDetailMessages(): array
    {
        return [
            'filters.array'   => '筛选条件必须是数组',
            'keyword.string'  => '关键字格式错误',
            'keyword.max'     => '关键字不能超过200个字符',
            'fileName.string' => '文件名称格式错误',
            'fileName.max'    => '文件名称不能超过200个字符',
        ];
    }

    private function getDepartmentPickingDetailRules(): array
    {
        return [
            'filters'  => [
                'nullable',
                'array',
                new SceneRule('ReportDepartmentPickingDetail')
            ],
            'keyword'  => 'nullable|string|max:200',
            'fileName' => 'nullable|string|max:200',
        ];
    }

    private function getDepartmentPickingDetailMessages(): array
    {
        return [
            'filters.array'   => '筛选条件必须是数组',
            'keyword.string'  => '关键字格式错误',
            'keyword.max'     => '关键字不能超过200个字符',
            'fileName.string' => '文件名称格式错误',
            'fileName.max'    => '文件名称不能超过200个字符',
        ];
    }

    private function getConsumableDetailRules(): array
    {
        return [
            'filters'  => [
                'nullable',
                'array',
                new SceneRule('ReportConsumableDetail')
            ],
            'keyword'  => 'nullable|string|max:200',
            'fileName' => 'nullable|string|max:200',
        ];
    }

    private function getConsumableDetailMessages(): array
    {
        return [
            'filters.array'   => '筛选条件必须是数组',
            'keyword.string'  => '关键字格式错误',
            'keyword.max'     => '关键字不能超过200个字符',
            'fileName.string' => '文件名称格式错误',
            'fileName.max'    => '文件名称不能超过200个字符',
        ];
    }

    private function getProductRankingRules(): array
    {
        return [
            'created_at'   => 'required|array|size:2',
            'created_at.*' => 'required|date',
            'medium_id'    => 'nullable|integer|exists:medium,id',
            'type_id'      => 'nullable|integer|exists:product_type,id',
            'sort'         => 'nullable|string|in:income,times,used,refund_times,leftover,payable,deposit,coupon,arrearage',
            'order'        => 'nullable|string|in:asc,desc',
            'fileName'     => 'nullable|string|max:200',
        ];
    }

    private function getProductRankingMessages(): array
    {
        return [
            'created_at.required'   => '[消费日期]不能为空',
            'created_at.array'      => '[消费日期]格式错误',
            'created_at.size'       => '[消费日期]必须包含开始和结束日期',
            'created_at.*.required' => '[消费日期]不能为空',
            'created_at.*.date'     => '[消费日期]格式错误',
            'medium_id.integer'     => '[媒介来源]格式错误',
            'medium_id.exists'      => '[媒介来源]不存在',
            'type_id.integer'       => '[项目分类]格式错误',
            'type_id.exists'        => '[项目分类]不存在',
            'sort.string'           => '[排序字段]格式错误',
            'sort.in'               => '[排序字段]值无效',
            'order.string'          => '[排序方向]格式错误',
            'order.in'              => '[排序方向]值无效',
            'fileName.string'       => '文件名称格式错误',
            'fileName.max'          => '文件名称不能超过200个字符',
        ];
    }

    private function getUserRules(): array
    {
        return [
            'keyword'       => 'nullable|string|max:200',
            'roles'         => 'nullable|integer|exists:roles,id',
            'department_id' => 'nullable|integer|exists:department,id',
            'fileName'      => 'nullable|string|max:200',
        ];
    }

    private function getUserMessages(): array
    {
        return [
            'keyword.string'        => '关键字格式错误',
            'keyword.max'           => '关键字不能超过200个字符',
            'roles.integer'         => '角色格式错误',
            'roles.exists'          => '角色不存在',
            'department_id.integer' => '部门格式错误',
            'department_id.exists'  => '部门不存在',
            'fileName.string'       => '文件名称格式错误',
            'fileName.max'          => '文件名称不能超过200个字符',
        ];
    }

    private function getAppointmentRules(): array
    {
        return [
            'filters'      => [
                'nullable',
                'array',
                new SceneRule('WorkbenchAppointment')
            ],
            'keyword'      => 'nullable|string|max:200',
            'created_at'   => 'required|array|size:2',
            'created_at.*' => 'required|date|date_format:Y-m-d',
            'fileName'     => 'nullable|string|max:200',
        ];
    }

    private function getAppointmentMessages(): array
    {
        return [
            'filters.array'            => '[场景化筛选条件]格式不正确',
            'keyword.string'           => '[顾客信息]格式错误',
            'keyword.max'              => '[顾客信息]不能超过200个字符',
            'created_at.required'      => '[查询时间]不能为空',
            'created_at.array'         => '[查询时间]格式不正确',
            'created_at.size'          => '[查询时间]格式不正确',
            'created_at.*.required'    => '[查询时间]格式不正确',
            'created_at.*.date'        => '[查询时间]格式不正确',
            'created_at.*.date_format' => '[查询时间]格式不正确',
            'fileName.string'          => '文件名称格式错误',
            'fileName.max'             => '文件名称不能超过200个字符',
        ];
    }

    private function getCustomerDepositDetailRules(): array
    {
        return [
            'date'              => 'required|array|size:2',
            'date.*'            => 'required|date',
            'keyword'           => 'nullable|string|max:200',
            'cashierable_type'  => 'nullable|string',
            'fileName'          => 'nullable|string|max:200',
        ];
    }

    private function getCustomerDepositDetailMessages(): array
    {
        return [
            'date.required'        => '[查询日期]不能为空',
            'date.array'           => '[查询日期]格式错误',
            'date.size'            => '[查询日期]必须包含开始和结束日期',
            'date.*.required'      => '[查询日期]不能为空',
            'date.*.date'          => '[查询日期]格式错误',
            'keyword.string'       => '关键字格式错误',
            'keyword.max'          => '关键字不能超过200个字符',
            'cashierable_type.string' => '业务类型格式错误',
            'fileName.string'      => '文件名称格式错误',
            'fileName.max'         => '文件名称不能超过200个字符',
        ];
    }

    /**
     * 获取导出请求参数
     * @return array
     */
    private function getExportParameter(): array
    {
        $method = request()->route()->getActionMethod();

        // 根据不同方法返回不同参数
        return match ($method) {
            'customer' => $this->only(['filters', 'keyword', 'group_id']),
            'customerLog' => $this->only(['created_at', 'customer_id', 'action', 'user_id']),
            'customerGoods', 'customerProduct', 'cashierRefund', 'purchaseDetail', 'departmentPickingDetail', 'consumableDetail' => $this->only(['filters', 'keyword']),
            'cashierPay', 'cashierDetail' => $this->only(['filters', 'date', 'keyword']),
            'customerDepositDetail' => $this->only(['date', 'keyword', 'cashierable_type']),
            'salesPerformance' => $this->only(['filters', 'created_at', 'keyword']),
            'customerIntegral' => $this->only(['created_at', 'type', 'keyword', 'expired']),
            'productRanking' => $this->only(['created_at', 'medium_id', 'type_id', 'sort', 'order']),
            'inventoryAlarm' => $this->only(['warehouse_id', 'type_id', 'name', 'status', 'filterable']),
            'inventoryExpiry' => $this->only(['warehouse_id', 'type_id', 'name', 'status', 'expiry_diff']),
            'user' => $this->only(['keyword', 'roles', 'department_id']),
            'appointment' => $this->only(['filters', 'keyword', 'created_at']),
            default => []
        };
    }
}
