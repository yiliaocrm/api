<?php

namespace App\Models;

use App\Enums\WorkflowExecutionStatus;
use App\Enums\WorkflowRunStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkflowRun extends BaseModel
{
    protected function casts(): array
    {
        return [
            'status' => WorkflowRunStatus::class,
            'group_ids_json' => 'array',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
            'cancel_requested_at' => 'datetime',
            'dispatch_completed_at' => 'datetime',
        ];
    }

    /**
     * 关联的工作流
     */
    public function workflow(): BelongsTo
    {
        return $this->belongsTo(Workflow::class);
    }

    /**
     * 工作流版本
     */
    public function workflowVersion(): BelongsTo
    {
        return $this->belongsTo(WorkflowVersion::class, 'workflow_version_id');
    }

    /**
     * 关联的执行记录
     */
    public function executions(): HasMany
    {
        return $this->hasMany(WorkflowExecution::class, 'run_id');
    }

    /**
     * 作用域：查询指定工作流的运行记录
     */
    public function scopeForWorkflow(Builder $query, int $workflowId): Builder
    {
        return $query->where('workflow_id', $workflowId);
    }

    /**
     * 作用域：查询待处理的运行记录
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', WorkflowRunStatus::PENDING);
    }

    /**
     * 作用域：查询运行中的运行记录
     */
    public function scopeRunning(Builder $query): Builder
    {
        return $query->where('status', WorkflowRunStatus::RUNNING);
    }

    /**
     * 启动运行记录
     */
    public function start(): bool
    {
        return $this->update([
            'status' => WorkflowRunStatus::RUNNING,
            'started_at' => now(),
        ]);
    }

    /**
     * 标记运行记录为完成
     */
    public function complete(): bool
    {
        return $this->update([
            'status' => WorkflowRunStatus::COMPLETED,
            'finished_at' => now(),
        ]);
    }

    /**
     * 标记运行记录为取消
     */
    public function cancel(): bool
    {
        return $this->update([
            'status' => WorkflowRunStatus::CANCELED,
            'finished_at' => now(),
        ]);
    }

    /**
     * 标记运行记录为错误
     */
    public function fail(string $errorMessage): bool
    {
        return $this->update([
            'status' => WorkflowRunStatus::ERROR,
            'error_message' => $errorMessage,
            'finished_at' => now(),
        ]);
    }

    /**
     * 请求取消
     */
    public function requestCancel(): bool
    {
        return $this->update([
            'cancel_requested_at' => now(),
        ]);
    }

    /**
     * 检查是否请求了取消
     */
    public function isCancelRequested(): bool
    {
        return $this->cancel_requested_at !== null;
    }

    /**
     * 推进游标
     */
    public function advanceCursor(string|int $customerId): bool
    {
        return $this->update([
            'cursor_last_customer_id' => $customerId,
        ]);
    }

    /**
     * 增加入队计数
     */
    public function incrementEnqueued(int $count = 1): bool
    {
        return $this->increment('enqueued_count', $count) > 0;
    }

    /**
     * 增加处理计数
     */
    public function incrementProcessed(int $count = 1): bool
    {
        return $this->increment('processed_count', $count) > 0;
    }

    /**
     * 增加成功计数
     */
    public function incrementSuccess(int $count = 1): bool
    {
        return $this->increment('success_count', $count) > 0;
    }

    /**
     * 增加错误计数
     */
    public function incrementError(int $count = 1): bool
    {
        return $this->increment('error_count', $count) > 0;
    }

    /**
     * 检查是否待处理
     */
    public function isPending(): bool
    {
        return $this->status === WorkflowRunStatus::PENDING;
    }

    /**
     * 设置目标总数
     */
    public function setTotalTarget(int $total): bool
    {
        return $this->update([
            'total_target' => $total,
        ]);
    }

    /**
     * 获取进度百分比
     */
    public function getProgressAttribute(): float
    {
        if ($this->total_target === 0) {
            return 0.0;
        }

        return round(($this->processed_count / $this->total_target) * 100, 2);
    }

    /**
     * 检查是否完成
     */
    public function isCompleted(): bool
    {
        return $this->status === WorkflowRunStatus::COMPLETED;
    }

    /**
     * 检查是否已取消
     */
    public function isCanceled(): bool
    {
        return $this->status === WorkflowRunStatus::CANCELED;
    }

    /**
     * 检查是否运行中
     */
    public function isRunning(): bool
    {
        return $this->status === WorkflowRunStatus::RUNNING;
    }

    /**
     * 检查是否错误态
     */
    public function isError(): bool
    {
        return $this->status === WorkflowRunStatus::ERROR;
    }

    /**
     * 检查是否终态
     */
    public function isTerminal(): bool
    {
        return in_array($this->status, [
            WorkflowRunStatus::COMPLETED,
            WorkflowRunStatus::CANCELED,
            WorkflowRunStatus::ERROR,
        ], true);
    }

    /**
     * 尝试收敛终态
     */
    public function tryConvergeTerminalState(): bool
    {
        if ($this->isTerminal()) {
            return false;
        }

        if (! $this->dispatch_completed_at) {
            return false;
        }

        if ($this->processed_count < $this->total_target) {
            return false;
        }

        $pendingExecutions = WorkflowExecution::query()
            ->where('run_id', $this->id)
            ->whereIn('status', [
                WorkflowExecutionStatus::RUNNING->value,
                WorkflowExecutionStatus::WAITING->value,
            ])
            ->exists();

        if ($pendingExecutions) {
            return false;
        }

        return $this->complete();
    }
}
