<?php

namespace App\Models;

use App\Observers\AttachmentUseObserver;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphPivot;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;

#[ObservedBy(AttachmentUseObserver::class)]
class AttachmentUse extends MorphPivot
{
    protected $table = 'attachment_uses';

    public $incrementing = true;

    protected function casts(): array
    {
        return [
            'sort' => 'integer',
        ];
    }

    /**
     * 关联附件
     */
    public function attachment(): BelongsTo
    {
        return $this->belongsTo(Attachment::class);
    }

    /**
     * 关联使用模型
     */
    public function usable(): MorphTo
    {
        return $this->morphTo();
    }
}
