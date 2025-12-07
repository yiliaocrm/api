<?php

namespace App\Models;

use App\Traits\HasTree;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class Medium extends BaseModel
{
    use HasTree;

    protected $table = 'medium';

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
        ];
    }

    /**
     * 解析关键词字段
     * @param $model
     * @return string
     */
    public static function parseKeyword($model): string
    {
        $fields = array_filter([
            $model->name,
            $model->contact,
            $model->phone,
            $model->address,
            $model->bank,
            $model->bank_account,
            $model->bank_name
        ]);
        return implode(',', parse_pinyin(implode(',', $fields)));
    }

    /**
     * 渠道负责人
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 创建人员
     * @return BelongsTo
     */
    public function createUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'create_user_id', 'id');
    }

    /**
     * 附件多态关联（通过 attachment_uses 表）
     * @return MorphToMany
     */
    public function attachments(): MorphToMany
    {
        return $this->morphToMany(
            Attachment::class,
            'usable',
            'attachment_uses',
            'usable_id',
            'attachment_id'
        )->using(AttachmentUse::class)->withPivot('sort')->withTimestamps()->orderBy('sort');
    }
}
