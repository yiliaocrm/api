<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkflowComponent extends BaseModel
{
    protected function casts(): array
    {
        return [
            'template' => 'json',
            'output_schema' => 'json',
        ];
    }

    /**
     * 组件类型
     */
    public function type(): BelongsTo
    {
        return $this->belongsTo(WorkflowComponentType::class);
    }
}
