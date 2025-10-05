<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SmsTemplate extends BaseModel
{
    protected function casts(): array
    {
        return [
            'disabled' => 'boolean',
        ];
    }

    /**
     * 模板分类
     * @return BelongsTo
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(SmsCategory::class, 'category_id');
    }

    /**
     * 使用场景
     * @return BelongsTo
     */
    public function scenario(): BelongsTo
    {
        return $this->belongsTo(SmsScenario::class, 'scenario_id');
    }
}
