<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class SmsCategory extends BaseModel
{
    protected static function boot(): void
    {
        parent::boot();

        static::created(function ($model) {
            $model->sort = $model->id;
            $model->save();
        });
    }

    /**
     * 分类下的短信模板
     * @return HasMany
     */
    public function templates(): HasMany
    {
        return $this->hasMany(SmsTemplate::class, 'category_id');
    }
}
