<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerGroupCategoryScope extends BaseModel
{
    /**
     * 获取关联的分类
     *
     * @return BelongsTo
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(CustomerGroupCategory::class, 'category_id');
    }
}
