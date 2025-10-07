<?php

namespace App\Models;

use App\Enums\ExportTaskStatus;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExportTask extends BaseModel
{
    protected function casts(): array
    {
        return [
            'params' => 'array',
            'status' => ExportTaskStatus::class,
        ];
    }

    /**
     * 导出任务创建人员
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 获取状态文本
     * @return Attribute
     */
    protected function statusText(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->status->getLabel(),
        );
    }
}
