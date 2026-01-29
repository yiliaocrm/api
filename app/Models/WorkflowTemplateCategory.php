<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkflowTemplateCategory extends BaseModel
{
    /**
     * 获取该分类下的所有模板
     */
    public function templates(): HasMany
    {
        return $this->hasMany(WorkflowTemplate::class, 'category_id');
    }
}
