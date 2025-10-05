<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerGroup extends BaseModel
{
    protected function casts(): array
    {
        return [
            'processing'   => 'boolean',
            'filter_rule'  => 'array',
            'exclude_rule' => 'array',
        ];
    }

    public static function boot(): void
    {
        parent::boot();
        static::deleted(fn($group) => $group->details()->delete());
    }

    /**
     * 分组明细数据
     * @return HasMany
     */
    public function details(): HasMany
    {
        return $this->hasMany(CustomerGroupDetail::class);
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
     * 分群分类
     * @return BelongsTo
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(CustomerGroupCategory::class, 'category_id');
    }
}
