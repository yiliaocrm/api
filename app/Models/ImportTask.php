<?php

namespace App\Models;

use App\Enums\ImportTaskStatus;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportTask extends BaseModel
{
    /**
     * 获取模型的类型转换
     */
    protected function casts(): array
    {
        return [
            'status' => ImportTaskStatus::class,
        ];
    }

    /**
     * 导入任务明细
     * @return HasMany
     */
    public function details(): HasMany
    {
        return $this->hasMany(ImportTaskDetail::class);
    }

    /**
     * 获取状态中文显示
     */
    public function statusText(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->status->getLabel(),
        );
    }

    /**
     * 关联模板
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(ImportTemplate::class);
    }

    public function importHeader(): Attribute
    {
        return Attribute::make(
            get: fn($value) => json_decode($value, true),
        );
    }
}
