<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class CustomerGroupCategory extends BaseModel
{
    /**
     * 模型启动方法
     */
    protected static function boot(): void
    {
        parent::boot();

        static::created(function ($model) {
            $model->sort = $model->id;
            $model->save();
        });
    }

    /**
     * 分组
     * @return HasMany
     */
    public function groups(): HasMany
    {
        return $this->hasMany(CustomerGroup::class, 'category_id');
    }

    /**
     * 可见范围
     * @return HasMany
     */
    public function scopeable(): HasMany
    {
        return $this->hasMany(CustomerGroupCategoryScope::class, 'category_id');
    }
}
