<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MediumAttachment extends BaseModel
{
    protected $guarded = [];

    /**
     * 缩略图
     * @return Attribute
     */
    public function thumb(): Attribute
    {
        return Attribute::make(
            get: fn($value) => get_attachment_url($value),
            set: fn($value) => str_replace(get_attachment_url(''), '', $value)
        );
    }

    /**
     * 文件路径
     * @return Attribute
     */
    public function filePath(): Attribute
    {
        return Attribute::make(
            get: fn($value) => get_attachment_url($value),
            set: fn($value) => str_replace(get_attachment_url(''), '', $value)
        );
    }

    /**
     * 关联媒介来源
     * @return BelongsTo
     */
    public function medium(): BelongsTo
    {
        return $this->belongsTo(Medium::class);
    }
}
