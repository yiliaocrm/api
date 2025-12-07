<?php

namespace App\Models;

use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttachmentThumbnail extends BaseModel
{
    protected function casts(): array
    {
        return [
            'width'     => 'integer',
            'height'    => 'integer',
            'file_size' => 'integer',
        ];
    }

    protected $appends = ['url'];

    public static function boot(): void
    {
        parent::boot();

        // 删除缩略图文件
        static::deleting(function ($thumbnail) {
            Storage::disk($thumbnail->disk)->delete($thumbnail->file_path);
        });
    }

    /**
     * 关联附件
     */
    public function attachment(): BelongsTo
    {
        return $this->belongsTo(Attachment::class);
    }

    /**
     * 获取URL属性
     */
    public function getUrlAttribute(): string
    {
        return get_attachment_url($this->file_path);
    }
}
