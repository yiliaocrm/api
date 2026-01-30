<?php

namespace App\Imports;

use App\Enums\ImportTaskDetailStatus;
use App\Models\ImportTaskDetail;
use App\Models\ProductType;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProductTypeImport extends BaseImport
{
    /**
     * 缓存已存在的分类ID，减少数据库查询
     * 格式: ["parent_id:name" => id]
     */
    protected array $typeCache = [];

    /**
     * 实际导入的数据处理
     */
    protected function handle(Collection $collection): mixed
    {
        $collection->each(function (ImportTaskDetail $item) {
            try {
                DB::transaction(function () use ($item) {
                    $this->processRow($item);
                    $item->update(['status' => ImportTaskDetailStatus::SUCCESS]);
                });
            } catch (\Throwable $e) {
                Log::error("ProductType import failed for detail ID {$item->id}", [
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
     * 处理单行数据
     */
    protected function processRow(ImportTaskDetail $item): void
    {
        $row = $item->row_data;
        $fullName = trim($row['分类名称'] ?? '');

        if (empty($fullName)) {
            return;
        }

        // 解析层级结构 (e.g. "所有分类/皮肤项目/激光紧肤")
        $levels = array_filter(explode('/', $fullName), fn ($value) => trim($value) !== '');
        $parentId = 0;

        foreach ($levels as $levelName) {
            $levelName = trim($levelName);

            // 构建缓存键
            $cacheKey = "{$parentId}:{$levelName}";

            if (isset($this->typeCache[$cacheKey])) {
                $parentId = $this->typeCache[$cacheKey];

                continue;
            }

            // 查找或创建分类
            // HasTree trait 会自动维护 tree、keyword、order、child 字段
            $type = ProductType::firstOrCreate(
                [
                    'name' => $levelName,
                    'parentid' => $parentId,
                ]
            );

            // 更新缓存和父级ID
            $this->typeCache[$cacheKey] = $type->id;
            $parentId = $type->id;
        }
    }

    /**
     * 验证规则
     * BaseImport 会自动使用这些规则进行预检测
     */
    public function rules(): array
    {
        return [
            '分类名称' => 'required|string',
        ];
    }
}
