<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkflowNode extends BaseModel
{
    /**
     * 获取节点类型
     */
    public function type(): BelongsTo
    {
        return $this->belongsTo(WorkflowNodeType::class, 'type_id');
    }

    /**
     * 获取节点所属的工作流
     */
    public function workflow(): BelongsTo
    {
        return $this->belongsTo(Workflow::class, 'workflow_id');
    }
}
