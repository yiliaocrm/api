<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkflowTemplate extends BaseModel
{
    protected $casts = [
        'config' => 'json'
    ];

    /**
     * 获取模板所属的分类
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(WorkflowTemplateCategory::class, 'category_id');
    }
}
