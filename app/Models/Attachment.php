<?php

namespace App\Models;

use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attachment extends BaseModel
{
    protected function casts(): array
    {
        return [
            'is_image'        => 'boolean',
            'download_count'  => 'integer',
            'reference_count' => 'integer',
            'last_used_at'    => 'datetime',
        ];
    }

    protected $appends = ['url'];

    public static function boot(): void
    {
        parent::boot();
        static::deleting(function ($attachment) {
            Storage::disk($attachment->disk)->delete($attachment->file_path);
        });
    }

    /**
     * 关联缩略图
     */
    public function thumbnails(): HasMany
    {
        return $this->hasMany(AttachmentThumbnail::class);
    }

    /**
     * 关联使用记录
     */
    public function uses(): HasMany
    {
        return $this->hasMany(AttachmentUse::class);
    }

    /**
     * 关联分组
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(AttachmentGroup::class, 'group_id');
    }

    /**
     * 关联下载记录
     */
    public function downloads(): HasMany
    {
        return $this->hasMany(AttachmentDownload::class);
    }

    /**
     * 获取URL属性
     */
    public function getUrlAttribute(): string
    {
        return get_attachment_url($this->file_path, $this->disk);
    }
}
