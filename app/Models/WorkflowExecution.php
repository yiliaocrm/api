<?php

namespace App\Models;

use App\Enums\WorkflowExecutionStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkflowExecution extends BaseModel
{
    protected function casts(): array
    {
        return [
            'status' => WorkflowExecutionStatus::class,
            'input_data' => 'array',
            'output_data' => 'array',
            'execution_data' => 'array',
            'context_data' => 'array',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
            'waiting_until' => 'datetime',
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
     * 获取执行时的工作流版本
     */
    public function workflowVersion(): BelongsTo
    {
        return $this->belongsTo(WorkflowVersion::class, 'workflow_version_id');
    }

    /**
     * 获取触发用户
     */
    public function triggerUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'trigger_user_id');
    }

    /**
     * Get execution steps.
     */
    public function steps(): HasMany
    {
        return $this->hasMany(WorkflowExecutionStep::class);
    }

    /**
     * 关联的运行记录
     */
    public function run(): BelongsTo
    {
        return $this->belongsTo(WorkflowRun::class, 'run_id');
    }

    /**
     * 作用域：查询指定运行记录的 executions
     */
    public function scopeByRun(Builder $query, int $runId): Builder
    {
        return $query->where('run_id', $runId);
    }

    /**
     * 作用域：查询待处理的 executions
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', WorkflowExecutionStatus::RUNNING);
    }

    /**
     * 作用域：查询指定运行记录中待处理的 executions
     */
    public function scopePendingByRun(Builder $query, int $runId): Builder
    {
        return $query->where('run_id', $runId)->where('status', WorkflowExecutionStatus::RUNNING);
    }

    /**
     * 作用域：按触发模型 ID 查询
     */
    public function scopeByTriggerModel(Builder $query, string $modelType, int $modelId): Builder
    {
        return $query->where('trigger_model_type', $modelType)->where('trigger_model_id', (string) $modelId);
    }

    /**
     * 作用域：查询指定运行记录中指定客户的 execution
     */
    public function scopeByRunAndTriggerModel(Builder $query, int $runId, string $modelType, int $modelId): Builder
    {
        return $query->where('run_id', $runId)
            ->where('trigger_model_type', $modelType)
            ->where('trigger_model_id', (string) $modelId);
    }
}
