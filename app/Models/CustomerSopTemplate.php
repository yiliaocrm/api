<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerSopTemplate extends BaseModel
{
    protected $casts = [
        'config' => 'json'
    ];

    /**
     * 获取模板所属的分类
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(CustomerSopTemplateCategory::class, 'category_id');
    }
}
