<?php

namespace App\Imports;

use App\Enums\ImportTaskDetailStatus;
use App\Models\Department;
use App\Models\ExpenseCategory;
use App\Models\ImportTaskDetail;
use App\Models\Product;
use App\Models\ProductType;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProductImport extends BaseImport
{
    /**
     * 缓存已存在的项目类别ID，减少数据库查询
     * 格式: ["parent_id:name" => id]
     */
    protected array $productTypeCache = [];

    /**
     * 缓存科室ID
     * 格式: ["name" => id]
     */
    protected array $departmentCache = [];

    /**
     * 缓存费用类别ID
     * 格式: ["name" => id]
     */
    protected array $expenseCategoryCache = [];

    /**
     * 实际导入的数据处理
     */
    protected function handle(Collection $collection): mixed
    {
        $this->preloadCaches($collection);

        $collection->each(function (ImportTaskDetail $item) {
            try {
                DB::transaction(function () use ($item) {
                    $this->processRow($item);
                    $item->update(['status' => ImportTaskDetailStatus::SUCCESS]);
                });
            } catch (\Throwable $e) {
                Log::error("Product import failed for detail ID {$item->id}", [
                    'error' => $e->getMessage(),
                    'row' => $item->row_data,
                ]);

                $item->update([
                    'status' => ImportTaskDetailStatus::FAILED,
                    'import_error_msg' => $e->getMessage(),
                ]);
            }
        });

        return true;
    }

    /**
     * 预加载缓存数据
     */
    protected function preloadCaches(Collection $collection): void
    {
        $rowDataList = $collection->pluck('row_data');

        // 预加载科室缓存
        $departmentNames = $rowDataList->pluck('结算科室')
            ->merge($rowDataList->pluck('划扣科室'))
            ->filter()
            ->unique()
            ->toArray();

        if (! empty($departmentNames)) {
            $departments = Department::query()
                ->whereIn('name', $departmentNames)
                ->get();

            foreach ($departments as $department) {
                $this->departmentCache[$department->name] = $department->id;
            }
        }

        // 预加载费用类别缓存
        $expenseCategoryNames = $rowDataList->pluck('费用类别')
            ->filter()
            ->unique()
            ->toArray();

        if (! empty($expenseCategoryNames)) {
            $expenseCategories = ExpenseCategory::query()
                ->whereIn('name', $expenseCategoryNames)
                ->get();

            foreach ($expenseCategories as $category) {
                $this->expenseCategoryCache[$category->name] = $category->id;
            }
        }
    }

    /**
     * 处理单行数据
     */
    protected function processRow(ImportTaskDetail $item): void
    {
        $row = $item->row_data;

        // 解析并获取/创建项目类别
        $typeId = $this->resolveProductType($row['项目类别'] ?? '');

        // 获取费用类别ID
        $expenseCategoryId = null;
        if (! empty($row['费用类别'])) {
            $expenseCategoryId = $this->expenseCategoryCache[$row['费用类别']] ?? null;
        }

        // 获取结算科室ID
        $departmentId = null;
        if (! empty($row['结算科室'])) {
            $departmentId = $this->departmentCache[$row['结算科室']] ?? null;
        }

        // 获取划扣科室ID
        $deductDepartmentId = null;
        if (! empty($row['划扣科室'])) {
            $deductDepartmentId = $this->departmentCache[$row['划扣科室']] ?? null;
        }

        // 创建产品
        Product::query()->create([
            'name' => $row['项目名称'],
            'type_id' => $typeId,
            'times' => $this->parseNumeric($row['项目次数'] ?? 0),
            'price' => $this->parseNumeric($row['项目原价'] ?? 0),
            'sales_price' => $this->parseNumeric($row['执行价格'] ?? 0),
            'specs' => $row['项目规格'] ?? null,
            'expiration' => $this->parseNumeric($row['使用期限'] ?? 0),
            'expense_category_id' => $expenseCategoryId,
            'department_id' => $departmentId,
            'deduct_department' => $deductDepartmentId,
            'deduct' => $this->parseBoolean($row['需要划扣'] ?? '否'),
            'commission' => $this->parseBoolean($row['开单提成'] ?? '否'),
            'integral' => $this->parseBoolean($row['消费积分'] ?? '否'),
            'successful' => $this->parseBoolean($row['统计成交'] ?? '否'),
            'remark' => $row['项目备注'] ?? null,
        ]);
    }

    /**
     * 解析项目类别路径，逐级查找/创建分类
     * 例如："所有分类/皮肤项目/激光紧肤/妊娠纹" 会返回 "妊娠纹" 分类的 ID
     */
    protected function resolveProductType(string $typePath): int
    {
        if (empty($typePath)) {
            throw new \InvalidArgumentException('项目类别不能为空');
        }

        // 解析层级结构
        $levels = array_filter(explode('/', $typePath), fn ($value) => trim($value) !== '');
        $parentId = 0;

        foreach ($levels as $levelName) {
            $levelName = trim($levelName);

            // 构建缓存键
            $cacheKey = "{$parentId}:{$levelName}";

            if (isset($this->productTypeCache[$cacheKey])) {
                $parentId = $this->productTypeCache[$cacheKey];

                continue;
            }

            // 查找或创建分类
            // HasTree trait 会自动维护 tree 结构
            $productType = ProductType::firstOrCreate(
                [
                    'name' => $levelName,
                    'parentid' => $parentId,
                ]
            );

            // 更新缓存和父级ID
            $this->productTypeCache[$cacheKey] = $productType->id;
            $parentId = $productType->id;
        }

        return $parentId;
    }

    /**
     * 解析数值
     */
    protected function parseNumeric($value): float|int
    {
        if (is_numeric($value)) {
            return $value;
        }

        return 0;
    }

    /**
     * 解析布尔值（是/否）
     */
    protected function parseBoolean($value): int
    {
        return ($value === '是' || $value === 'yes' || $value === '1' || $value === 1) ? 1 : 0;
    }

    /**
     * 验证规则
     * BaseImport 会自动使用这些规则进行预检测
     */
    public function rules(): array
    {
        return [
            '项目名称' => 'required|string|max:255',
            '项目类别' => 'required|string',
            '项目次数' => 'nullable|numeric',
            '项目原价' => 'nullable|numeric',
            '执行价格' => 'nullable|numeric',
            '项目规格' => 'nullable|string',
            '使用期限' => 'nullable|numeric',
            '费用类别' => 'nullable|exists:expense_category,name',
            '结算科室' => 'nullable|exists:department,name',
            '划扣科室' => 'nullable|exists:department,name',
            '需要划扣' => 'nullable|in:"是","否"',
            '开单提成' => 'nullable|in:"是","否"',
            '消费积分' => 'nullable|in:"是","否"',
            '统计成交' => 'nullable|in:"是","否"',
            '项目备注' => 'nullable|string',
        ];
    }
}
