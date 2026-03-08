<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkflowComponentType extends BaseModel
{
    /**
     * 获取所属组件
     */
    public function components(): HasMany
    {
        return $this->hasMany(WorkflowComponent::class);
    }
}
