<?php

namespace App\Models;

use App\Enums\ImportTaskDetailStatus;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportTaskDetail extends BaseModel
{
    /**
     * 获取模型的类型转换
     */
    protected function casts(): array
    {
        return [
            'status' => ImportTaskDetailStatus::class,
        ];
    }

    /**
     * 导入任务
     * @return BelongsTo
     */
    public function task(): BelongsTo
    {
        return $this->belongsTo(ImportTask::class);
    }

    /**
     * 保存行数据
     *
     * @return Attribute
     */
    public function rowData(): Attribute
    {
        return Attribute::make(
            get: fn($value) => json_decode($value, true),
            set: fn($value) => json_encode($value, JSON_UNESCAPED_UNICODE),
        );
    }

    /**
     * 保存错误信息
     *
     * @return Attribute
     */
    public function errorMsg(): Attribute
    {
        return Attribute::make(
            get: fn($value) => json_decode($value, true),
            set: fn($value) => json_encode($value, JSON_UNESCAPED_UNICODE),
        );
    }

    /**
     * 导入状态文本
     * @return Attribute
     */
    public function statusText(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->status->getLabel(),
        );
    }
}
