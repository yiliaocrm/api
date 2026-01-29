<?php

namespace App\Models;

use App\Enums\WorkflowExecutionStatus;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkflowExecution extends BaseModel
{
    protected function casts(): array
    {
        return [
            'status' => WorkflowExecutionStatus::class,
            'input_data' => 'array',
            'output_data' => 'array',
            'execution_data' => 'array',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    /**
     * 获取所属工作流
     */
    public function workflow(): BelongsTo
    {
        return $this->belongsTo(Workflow::class);
    }

    /**
     * 获取触发用户
     */
    public function triggerUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'trigger_user_id');
    }
}
