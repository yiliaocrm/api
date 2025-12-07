<?php

namespace App\Models;

use App\Traits\HasTree;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttachmentGroup extends BaseModel
{
    use HasTree;

    protected function casts(): array
    {
        return [
            'system' => 'boolean',
        ];
    }

    /**
     * 自定义父节点字段名
     */
    protected static function parentidField(): string
    {
        return 'parent_id';
    }

    /**
     * 关联附件
     */
    public function attachments(): HasMany|AttachmentGroup
    {
        return $this->hasMany(Attachment::class, 'group_id');
    }

    /**
     * 父分组
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(AttachmentGroup::class, 'parent_id');
    }

    /**
     * 子分组
     */
    public function children(): HasMany
    {
        return $this->hasMany(AttachmentGroup::class, 'parent_id')->orderBy('order');
    }
}
