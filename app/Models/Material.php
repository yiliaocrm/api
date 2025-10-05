<?php

namespace App\Models;

use App\Traits\FsidTrait;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Material extends BaseModel
{
    const TYPE_1 = 1;
    const TYPE_2 = 2;
    const TYPE_3 = 3;
    const TYPE_4 = 4;
    const TYPE_MAP = [
        Material::TYPE_1 => '文本素材',
        Material::TYPE_2 => '图片素材',
        Material::TYPE_3 => '文章素材',
        Material::TYPE_4 => '视频素材',
    ];

    use FsidTrait;

    public function getFsidKey(): string
    {
        return 'mid';
    }

    /**
     * 返回缩略图完整url
     * @param $value
     * @return string|void
     */
    public function getThumbAttribute($value)
    {
        if (!empty($value)) {
            return get_attachment_url($value);
        }
    }

    /**
     * 返回视频封面完整url
     * @param $value
     * @return string|void
     */
    public function getCoverImageAttribute($value)
    {
        if (!empty($value)) {
            return get_attachment_url($value);
        }
    }

    /**
     * 返回视频完整url
     * @param $value
     * @return string|void
     */
    public function getCoverVideoAttribute($value)
    {
        if (!empty($value)) {
            return get_attachment_url($value);
        }
    }

    /**
     * 创建人
     * @return BelongsTo
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    /**
     * 素材统计分析
     * @return HasOne
     */
    public function statistics(): HasOne
    {
        return $this->hasOne(MaterialStatistics::class);
    }

    /**
     * 素材分类
     * @return BelongsTo
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(MaterialCategory::class, 'material_category_id');
    }
}
