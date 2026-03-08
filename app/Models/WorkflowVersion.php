<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkflowVersion extends BaseModel
{
    protected function casts(): array
    {
        return [
            'version_no' => 'integer',
            'snapshot' => 'array',
        ];
    }

    /**
     * 所属工作流
     */
    public function workflow(): BelongsTo
    {
        return $this->belongsTo(Workflow::class, 'workflow_id');
    }

    /**
     * 创建人
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'create_user_id');
    }
}
