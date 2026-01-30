<?php

namespace App\Imports;

use App\Enums\ImportTaskDetailStatus;
use App\Models\Address;
use App\Models\ImportTaskDetail;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AddressImport extends BaseImport
{
    /**
     * 缓存已存在的地区ID，减少数据库查询
     * 格式: ["parent_id:name" => id]
     */
    protected array $addressCache = [];

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
                Log::error("Address import failed for detail ID {$item->id}", [
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
        $fullName = trim($row['地区名称'] ?? '');

        if (empty($fullName)) {
            return;
        }

        // 解析层级结构 (e.g. "广东省/广州市/天河区")
        $levels = array_filter(explode('/', $fullName), fn ($value) => trim($value) !== '');
        $parentId = 0;

        foreach ($levels as $levelName) {
            $levelName = trim($levelName);

            // 构建缓存键
            $cacheKey = "{$parentId}:{$levelName}";

            if (isset($this->addressCache[$cacheKey])) {
                $parentId = $this->addressCache[$cacheKey];

                continue;
            }

            // 查找或创建地区
            // HasTree trait 会自动维护 tree 结构
            $address = Address::firstOrCreate(
                [
                    'name' => $levelName,
                    'parentid' => $parentId,
                ]
            );

            // 更新缓存和父级ID
            $this->addressCache[$cacheKey] = $address->id;
            $parentId = $address->id;
        }
    }

    /**
     * 验证规则
     * BaseImport 会自动使用这些规则进行预检测
     */
    public function rules(): array
    {
        return [
            '地区名称' => 'required|string',
        ];
    }
}
