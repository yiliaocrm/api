<?php

namespace App\Models;

use App\Enums\WorkflowStatus;
use App\Enums\WorkflowType;
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
            'active' => 'boolean',
            'all_customer' => 'boolean',
            'type' => WorkflowType::class,
            'status' => WorkflowStatus::class,
            'nodes' => 'array',
            'connections' => 'array',
            'settings' => 'array',
            'static_data' => 'array',
            'tags' => 'array',
            'config' => 'array',
            'rule_chain' => 'array',
            'start_at' => 'datetime',
            'end_at' => 'datetime',
            'last_run_at' => 'datetime',
            'next_run_at' => 'datetime',
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
        return $this->belongsToMany(CustomerGroup::class, 'workflow_customer_groups', 'workflow_id', 'customer_group_id');
    }

    /**
     * 工作流执行记录
     */
    public function executions(): HasMany
    {
        return $this->hasMany(WorkflowExecution::class);
    }

    /**
     * 激活工作流
     */
    public function activate(): bool
    {
        return $this->update(['active' => true, 'status' => WorkflowStatus::ACTIVE]);
    }

    /**
     * 停用工作流
     */
    public function deactivate(): bool
    {
        return $this->update(['active' => false, 'status' => WorkflowStatus::PAUSED]);
    }

    /**
     * 转换为 n8n 格式
     */
    public function toN8nFormat(): array
    {
        return [
            'id' => $this->n8n_id,
            'name' => $this->name,
            'active' => $this->active,
            'nodes' => $this->nodes ?? [],
            'connections' => $this->connections ?? [],
            'settings' => $this->settings ?? [],
            'staticData' => $this->static_data ?? [],
            'tags' => $this->tags ?? [],
        ];
    }
}
