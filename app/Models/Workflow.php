<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Workflow extends BaseModel
{
    protected function casts(): array
    {
        return [
            'all_customer' => 'boolean',
        ];
    }

    /**
     * 分类
     * @return BelongsTo
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(WorkflowCategory::class, 'category_id');
    }

    /**
     * 创建人员
     * @return BelongsTo
     */
    public function createUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'create_user_id');
    }

    /**
     * 工作流目标人群
     * @return BelongsToMany
     */
    public function customerGroups(): BelongsToMany
    {
        return $this->belongsToMany(CustomerGroup::class, 'workflow_customer_groups', 'workflow_id', 'customer_group_id');
    }
}
