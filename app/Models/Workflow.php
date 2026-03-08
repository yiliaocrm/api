<?php

namespace App\Models;

use App\Enums\WorkflowStatus;
use App\Enums\WorkflowType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Workflow extends BaseModel
{
    use SoftDeletes;

    protected function casts(): array
    {
        return [
            'all_customer' => 'boolean',
            'type' => WorkflowType::class,
            'status' => WorkflowStatus::class,
            'cron' => 'array',
            'rule_chain' => 'array',
            'last_run_at' => 'datetime',
            'next_run_at' => 'datetime',
            'dispatch_chunk_size' => 'integer',
            'dispatch_concurrency' => 'integer',
            'execution_batch_size' => 'integer',
            'max_queue_lag' => 'integer',
        ];
    }

    /**
     * 分类
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(WorkflowCategory::class, 'category_id');
    }

    /**
     * 创建人员
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'create_user_id');
    }

    /**
     * 创建人员（别名，保持向后兼容）
     */
    public function createUser(): BelongsTo
    {
        return $this->creator();
    }

    /**
     * 工作流目标人群
     */
    public function customerGroups(): BelongsToMany
    {
        return $this->belongsToMany(CustomerGroup::class, 'workflow_customer_groups', 'workflow_id', 'customer_group_id')
            ->withTimestamps();
    }

    /**
     * 工作流执行记录
     */
    public function executions(): HasMany
    {
        return $this->hasMany(WorkflowExecution::class);
    }

    /**
     * 工作流历史版本
     */
    public function versions(): HasMany
    {
        return $this->hasMany(WorkflowVersion::class, 'workflow_id');
    }

    /**
     * 工作流运行记录
     */
    public function runs(): HasMany
    {
        return $this->hasMany(WorkflowRun::class);
    }

    /**
     * 发布工作流
     */
    public function activate(): bool
    {
        return $this->update(['status' => WorkflowStatus::ACTIVE]);
    }

    /**
     * 取消发布工作流
     */
    public function deactivate(): bool
    {
        return $this->update(['status' => WorkflowStatus::PAUSED]);
    }

    /**
     * 从 rule_chain 中提取周期调度配置
     *
     * @return array<string, mixed>|null
     */
    public function getPeriodicConfigAttribute(): ?array
    {
        $ruleChain = is_array($this->rule_chain) ? $this->rule_chain : [];
        $nodes = is_array($ruleChain['nodes'] ?? null) ? $ruleChain['nodes'] : [];

        foreach ($nodes as $node) {
            if (($node['type'] ?? null) !== 'start_periodic') {
                continue;
            }

            foreach (['parameters', 'formData', 'props'] as $field) {
                $value = $node[$field] ?? null;
                if (is_array($value)) {
                    return $value;
                }
            }

            return [];
        }

        return null;
    }

    /**
     * 查询作用域：到期待调度的周期型工作流
     */
    public function scopePeriodicDue(Builder $query): Builder
    {
        return $query
            ->where('type', WorkflowType::PERIODIC)
            ->where('status', WorkflowStatus::ACTIVE)
            ->whereNotNull('next_run_at')
            ->where('next_run_at', '<=', now());
    }
}
