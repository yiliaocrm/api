<?php

namespace App\Imports;

use App\Enums\ImportTaskDetailStatus;
use App\Models\ImportTaskDetail;
use App\Models\Medium;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MediumImport extends BaseImport
{
    /**
     * 缓存已存在的媒介ID，减少数据库查询
     * 格式: ["parent_id:name" => id]
     */
    protected array $mediumCache = [];

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
                Log::error("Medium import failed for detail ID {$item->id}", [
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
        $fullName = trim($row['媒介名称'] ?? '');

        if (empty($fullName)) {
            return;
        }

        // 解析时间，如果为空则使用当前时间
        $now = now();
        $createdAt = ! empty($row['创建时间']) ? Carbon::createFromFormat('Y-m-d', $row['创建时间']) : $now;
        $updatedAt = ! empty($row['更新时间']) ? Carbon::createFromFormat('Y-m-d', $row['更新时间']) : $now;

        // 解析层级结构 (e.g. "一级来源/二级来源")
        $levels = array_filter(explode('/', $fullName), fn ($value) => trim($value) !== '');
        $parentId = 0;

        foreach ($levels as $levelName) {
            $levelName = trim($levelName);

            // 构建缓存键
            $cacheKey = "{$parentId}:{$levelName}";

            if (isset($this->mediumCache[$cacheKey])) {
                $parentId = $this->mediumCache[$cacheKey];

                continue;
            }

            // 查找或创建媒介
            // HasTree trait 会自动维护 tree 结构
            $medium = Medium::firstOrCreate(
                [
                    'name' => $levelName,
                    'parentid' => $parentId,
                ],
                [
                    'create_user_id' => 1,
                    'created_at' => $createdAt,
                    'updated_at' => $updatedAt,
                ]
            );

            // 更新缓存和父级ID
            $this->mediumCache[$cacheKey] = $medium->id;
            $parentId = $medium->id;
        }
    }

    /**
     * 验证规则
     * BaseImport 会自动使用这些规则进行预检测
     */
    public function rules(): array
    {
        return [
            '媒介名称' => 'required|string',
            '创建时间' => 'nullable|date_format:Y-m-d',
            '更新时间' => 'nullable|date_format:Y-m-d',
        ];
    }
}
