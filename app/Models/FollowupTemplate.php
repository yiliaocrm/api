<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FollowupTemplate extends BaseModel
{
    protected $table = 'followup_template';

    public static function boot(): void
    {
        parent::boot();

        static::saving(function ($template) {
            $template->keyword = implode(',', parse_pinyin($template->title));
        });
    }

    /**
     * 模板分类
     * @return BelongsTo
     */
    public function type(): BelongsTo
    {
        return $this->belongsTo(FollowupTemplateType::class);
    }

    /**
     * 模板明细
     * @return HasMany
     */
    public function details(): HasMany
    {
        return $this->hasMany(FollowupTemplateDetail::class);
    }

    /**
     * 创建人员
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
